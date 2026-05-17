# v2_analyze_04_operatorwirkung_v2.py

import json
from pathlib import Path
from typing import Any, Dict, List, Optional

import pandas as pd
import matplotlib.pyplot as plt


INFILE = Path("auswertung_04_operatorwirkung.json")
OUTDIR = Path("auswertung_04_operatorwirkung_output_v2")
OUTDIR.mkdir(exist_ok=True)

SCOPES_LABELS = {
    "alle_lehrkraefte": "Alle Lehrkräfte",
    "lehrkraft_1": "Lehrkraft 1",
    "alle_ausser_lehrkraft_1": "Nicht Lehrkraft 1",
}

DIMENSIONS = [
    "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
    "x_methodik", "x_performanz", "x_regulation"
]

DIM_LABELS = {
    "x_kognition": "Kognition",
    "x_sozial": "Sozial",
    "x_affektiv": "Affektiv",
    "x_motivation": "Motivation",
    "x_methodik": "Methodik",
    "x_performanz": "Performanz",
    "x_regulation": "Regulation",
}


def fmt(x: Any, digits: int = 4) -> str:
    if x is None:
        return "n/a"
    try:
        if pd.isna(x):
            return "n/a"
        return f"{float(x):.{digits}f}"
    except Exception:
        return str(x)


def ensure_df(records: Optional[List[Dict[str, Any]]]) -> pd.DataFrame:
    if not records:
        return pd.DataFrame()
    return pd.DataFrame(records)


def load_json() -> Dict[str, Any]:
    if not INFILE.exists():
        raise FileNotFoundError(
            f"JSON-Datei nicht gefunden: {INFILE.resolve()}\n"
            "Bitte zuerst export_04_operatorwirkung.py ausführen."
        )
    return json.loads(INFILE.read_text(encoding="utf-8"))


def get_scope_df(data: Dict[str, Any], scope: str, key: str) -> pd.DataFrame:
    return ensure_df(data.get("scopes", {}).get(scope, {}).get(key, []))


def save_csv(df: pd.DataFrame, filename: str) -> None:
    if not df.empty:
        df.to_csv(OUTDIR / filename, index=False, encoding="utf-8-sig")


def to_numeric_clean(df: pd.DataFrame, cols: List[str]) -> pd.DataFrame:
    df = df.copy()
    for col in cols:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    return df


def has_both_operator_groups(op_df: pd.DataFrame) -> bool:
    if op_df.empty or "has_operator" not in op_df.columns:
        return False

    tmp = op_df.copy()
    tmp["has_operator"] = pd.to_numeric(tmp["has_operator"], errors="coerce")
    values = set(tmp["has_operator"].dropna().astype(int).tolist())
    return values >= {0, 1}


def operator_rows(op_df: pd.DataFrame):
    if not has_both_operator_groups(op_df):
        return None, None

    tmp = op_df.copy()
    tmp["has_operator"] = pd.to_numeric(tmp["has_operator"], errors="coerce")

    without = tmp[tmp["has_operator"].astype(int) == 0].iloc[0]
    withop = tmp[tmp["has_operator"].astype(int) == 1].iloc[0]

    return without, withop


def delta_value(without, withop, metric: str):
    if without is None or withop is None:
        return None
    if metric not in without.index or metric not in withop.index:
        return None

    a = pd.to_numeric(pd.Series([without[metric]]), errors="coerce").iloc[0]
    b = pd.to_numeric(pd.Series([withop[metric]]), errors="coerce").iloc[0]

    if pd.isna(a) or pd.isna(b):
        return None

    return float(b) - float(a)


def plot_operator_bar(
    op_df: pd.DataFrame,
    scope: str,
    metric: str,
    ylabel: str,
    title: str,
    filename: str
) -> None:
    if op_df.empty or metric not in op_df.columns or "has_operator" not in op_df.columns:
        return

    df = op_df[["has_operator", metric]].copy()
    df["has_operator"] = pd.to_numeric(df["has_operator"], errors="coerce")
    df[metric] = pd.to_numeric(df[metric], errors="coerce")
    df = df.dropna(subset=["has_operator", metric])

    if df.empty:
        return

    df["label"] = df["has_operator"].astype(int).map({
        0: "ohne Operator",
        1: "mit Operator"
    })

    df = df.sort_values("has_operator")

    plt.figure(figsize=(8, 5))
    plt.bar(df["label"].astype(str), df[metric].astype(float))
    plt.title(f"{title} – {SCOPES_LABELS.get(scope, scope)}")
    plt.ylabel(ylabel)
    plt.tight_layout()
    plt.savefig(OUTDIR / filename, dpi=300)
    plt.close()


def plot_polarity(pol_df: pd.DataFrame, scope: str) -> None:
    if pol_df.empty or "polaritaet_gesamt" not in pol_df.columns:
        return

    df = pol_df.copy()
    df["polaritaet_gesamt"] = pd.to_numeric(df["polaritaet_gesamt"], errors="coerce")
    df = df.dropna(subset=["polaritaet_gesamt"]).sort_values("polaritaet_gesamt")

    if df.empty:
        return

    for metric, ylabel, suffix in [
        ("mean_dichte", "mittlere semantische Dichte", "polaritaet_mean_dichte"),
        ("mean_delta_vector", "mittlere Delta-Vektor-Norm", "polaritaet_delta_vector"),
        ("var_dichte", "Varianz semantische Dichte", "polaritaet_varianz"),
    ]:
        if metric not in df.columns:
            continue

        plot_df = df[["polaritaet_gesamt", metric]].copy()
        plot_df[metric] = pd.to_numeric(plot_df[metric], errors="coerce")
        plot_df = plot_df.dropna(subset=[metric])

        if plot_df.empty:
            continue

        plt.figure(figsize=(8, 5))
        plt.bar(plot_df["polaritaet_gesamt"].astype(int).astype(str), plot_df[metric].astype(float))
        plt.title(f"Polaritätsgruppen: {ylabel} – {SCOPES_LABELS.get(scope, scope)}")
        plt.xlabel("Polarität")
        plt.ylabel(ylabel)
        plt.tight_layout()
        plt.savefig(OUTDIR / f"{scope}_{suffix}.png", dpi=300)
        plt.close()


def plot_time_series(time_df: pd.DataFrame, scope: str) -> None:
    if time_df.empty or "datum" not in time_df.columns:
        return

    df = time_df.copy()
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    df = df.dropna(subset=["datum"]).sort_values("datum")

    if df.empty:
        return

    series = [
        ("mean_dichte", "mittlere semantische Dichte", "zeitreihe_mean_dichte"),
        ("var_dichte", "Varianz semantische Dichte", "zeitreihe_varianz"),
        ("mean_delta_vector", "mittlere Delta-Vektor-Norm", "zeitreihe_delta_vector"),
        ("mean_polaritaet_shift", "Polaritätsverschiebung", "zeitreihe_polaritaet_shift"),
        ("operator_anteil", "Anteil operatorhaltiger Sätze", "zeitreihe_operatoranteil"),
    ]

    for metric, ylabel, suffix in series:
        if metric not in df.columns:
            continue

        plot_df = df[["datum", metric]].copy()
        plot_df[metric] = pd.to_numeric(plot_df[metric], errors="coerce")
        plot_df = plot_df.dropna(subset=[metric])

        if plot_df.empty:
            continue

        plt.figure(figsize=(10, 5))
        plt.plot(plot_df["datum"], plot_df[metric].astype(float), marker="o")
        plt.title(f"{ylabel} im Zeitverlauf – {SCOPES_LABELS.get(scope, scope)}")
        plt.xlabel("Datum")
        plt.ylabel(ylabel)
        plt.xticks(rotation=45)
        plt.tight_layout()
        plt.savefig(OUTDIR / f"{scope}_{suffix}.png", dpi=300)
        plt.close()


def plot_scatter(raw_df: pd.DataFrame, scope: str) -> None:
    needed = {"d_semantisch", "delta_vector_norm", "has_operator"}
    if raw_df.empty or not needed.issubset(raw_df.columns):
        return

    df = raw_df[list(needed)].copy()
    df = to_numeric_clean(df, ["d_semantisch", "delta_vector_norm", "has_operator"])
    df = df.dropna(subset=["d_semantisch", "delta_vector_norm", "has_operator"])

    if df.empty:
        return

    plt.figure(figsize=(8, 6))

    for value, label in [(0, "ohne Operator"), (1, "mit Operator")]:
        part = df[df["has_operator"].astype(int) == value]
        if not part.empty:
            plt.scatter(
                part["d_semantisch"].astype(float),
                part["delta_vector_norm"].astype(float),
                label=label,
                alpha=0.75
            )

    plt.title(f"Operatorwirkung: Dichte vs. Delta-Bewegung – {SCOPES_LABELS.get(scope, scope)}")
    plt.xlabel("semantische Dichte")
    plt.ylabel("Delta-Vektor-Norm")
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTDIR / f"{scope}_scatter_dichte_vs_delta_operator.png", dpi=300)
    plt.close()


def plot_dimension_means(raw_df: pd.DataFrame, scope: str) -> None:
    if raw_df.empty or "has_operator" not in raw_df.columns:
        return

    available_dims = [d for d in DIMENSIONS if d in raw_df.columns]
    if not available_dims:
        return

    df = raw_df[["has_operator", *available_dims]].copy()
    df = to_numeric_clean(df, ["has_operator", *available_dims])
    df = df.dropna(subset=["has_operator"])

    if df.empty:
        return

    means = df.groupby("has_operator")[available_dims].mean().T

    if means.empty:
        return

    means.index = [DIM_LABELS.get(d, d) for d in means.index]

    plt.figure(figsize=(11, 5))

    x = list(range(len(means.index)))
    width = 0.35

    if 0 in means.columns:
        plt.bar([i - width / 2 for i in x], means[0].astype(float), width=width, label="ohne Operator")

    if 1 in means.columns:
        plt.bar([i + width / 2 for i in x], means[1].astype(float), width=width, label="mit Operator")

    plt.xticks(x, means.index, rotation=35, ha="right")
    plt.title(f"Dimensionale Mittelwerte nach Operatorstatus – {SCOPES_LABELS.get(scope, scope)}")
    plt.ylabel("mittlerer Dimensionswert")
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTDIR / f"{scope}_dimensionale_mittelwerte_operatorstatus.png", dpi=300)
    plt.close()


def plot_cross_scope_summary(summary_df: pd.DataFrame) -> None:
    if summary_df is None or summary_df.empty:
        print("Keine Summary-Daten für gruppenübergreifende Plots vorhanden.")
        return

    if "gruppe" not in summary_df.columns:
        print("Spalte 'gruppe' fehlt in summary_df.")
        return

    metrics = [
        ("delta_mean_dichte", "Δ mittlere Dichte", "abb_1_operator_delta_mean_dichte.png"),
        ("delta_var_dichte", "Δ Varianz", "abb_2_operator_delta_varianz.png"),
        ("delta_delta_vector", "Δ Delta-Bewegung", "abb_3_operator_delta_bewegung.png"),
        ("delta_polaritaet_shift", "Δ Polaritätsverschiebung", "abb_4_operator_delta_polaritaet.png"),
        ("anteil_operatorhaltig", "Anteil operatorhaltiger Sätze", "abb_5_operatoranteil.png"),
    ]

    for metric, ylabel, filename in metrics:
        if metric not in summary_df.columns:
            continue

        plot_df = summary_df[["gruppe", metric]].copy()
        plot_df[metric] = pd.to_numeric(plot_df[metric], errors="coerce")
        plot_df = plot_df.dropna(subset=[metric])

        if plot_df.empty:
            print(f"Überspringe Plot für {metric}: keine numerischen Werte vorhanden.")
            continue

        plt.figure(figsize=(9, 5))
        plt.bar(plot_df["gruppe"].astype(str), plot_df[metric].astype(float))
        plt.title(f"{ylabel} durch Operatoren nach Lehrkraftgruppe")
        plt.ylabel(ylabel)
        plt.xticks(rotation=20, ha="right")
        plt.tight_layout()
        plt.savefig(OUTDIR / filename, dpi=300)
        plt.close()

        print(f"Grafik erzeugt: {filename}")


def build_summary(data: Dict[str, Any]) -> pd.DataFrame:
    rows = []

    for scope in data.get("scopes", {}).keys():
        op_df = get_scope_df(data, scope, "operatorvergleich")
        without, withop = operator_rows(op_df)

        row = {
            "scope": scope,
            "gruppe": SCOPES_LABELS.get(scope, scope),
            "n_gesamt": data["scopes"][scope].get("n"),
            "operatorhaltige_saetze": data["scopes"][scope].get("operatorhaltige_saetze"),
            "anteil_operatorhaltig": data["scopes"][scope].get("anteil_operatorhaltig"),

            "mean_dichte_ohne_operator": None if without is None else without.get("mean_dichte"),
            "mean_dichte_mit_operator": None if withop is None else withop.get("mean_dichte"),
            "delta_mean_dichte": delta_value(without, withop, "mean_dichte"),

            "var_dichte_ohne_operator": None if without is None else without.get("var_dichte"),
            "var_dichte_mit_operator": None if withop is None else withop.get("var_dichte"),
            "delta_var_dichte": delta_value(without, withop, "var_dichte"),

            "mean_delta_vector_ohne_operator": None if without is None else without.get("mean_delta_vector"),
            "mean_delta_vector_mit_operator": None if withop is None else withop.get("mean_delta_vector"),
            "delta_delta_vector": delta_value(without, withop, "mean_delta_vector"),

            "mean_polaritaet_shift_ohne_operator": None if without is None else without.get("mean_polaritaet_shift"),
            "mean_polaritaet_shift_mit_operator": None if withop is None else withop.get("mean_polaritaet_shift"),
            "delta_polaritaet_shift": delta_value(without, withop, "mean_polaritaet_shift"),
        }

        rows.append(row)

    return pd.DataFrame(rows)


def markdown_table(df: pd.DataFrame, cols: List[str], headers: List[str]) -> str:
    if df.empty:
        return "_Keine Daten verfügbar._"

    lines = []
    lines.append("| " + " | ".join(headers) + " |")
    lines.append("| " + " | ".join(["---"] * len(headers)) + " |")

    for _, row in df.iterrows():
        values = []
        for col in cols:
            value = row.get(col, None)
            if isinstance(value, float) or isinstance(value, int):
                values.append(fmt(value))
            else:
                values.append(str(value) if value is not None else "n/a")
        lines.append("| " + " | ".join(values) + " |")

    return "\n".join(lines)


def interpret_scope(scope: str, op_df: pd.DataFrame) -> str:
    label = SCOPES_LABELS.get(scope, scope)

    if op_df.empty:
        return f"Für {label} liegen keine Operatorvergleichsdaten vor."

    without, withop = operator_rows(op_df)

    if without is None or withop is None:
        return (
            f"Für {label} liegen nicht beide Vergleichsgruppen vor. "
            "Der Vergleich mit Operator versus ohne Operator ist daher nicht vollständig belastbar."
        )

    d_mean = delta_value(without, withop, "mean_dichte")
    d_var = delta_value(without, withop, "var_dichte")
    d_delta = delta_value(without, withop, "mean_delta_vector")
    d_shift = delta_value(without, withop, "mean_polaritaet_shift")

    text = []
    text.append(
        f"Für {label} zeigt der Operatorvergleich eine Veränderung der mittleren semantischen Dichte um {fmt(d_mean)}, "
        f"eine Veränderung der Varianz um {fmt(d_var)}, eine Veränderung der Delta-Bewegung um {fmt(d_delta)} "
        f"und eine Veränderung der Polaritätsverschiebung um {fmt(d_shift)}."
    )

    if d_delta is not None and d_delta > 0:
        text.append(
            "FRZK-konform spricht die erhöhte Delta-Bewegung dafür, dass Operatoren den Folgeraum nicht nur lokal markieren, "
            "sondern den Zustandsvektor insgesamt stärker verschieben."
        )
    elif d_delta is not None:
        text.append(
            "Die Delta-Bewegung ist nicht erhöht. Die Operatorwirkung zeigt sich in diesem Scope daher nicht primär als Bewegungsverstärkung."
        )

    if d_var is not None and d_var > 0:
        text.append(
            "Die erhöhte Varianz weist auf eine breitere Streuung des semantischen Folgeraums hin. Operatoren wirken hier als Differenzierungsimpulse."
        )
    elif d_var is not None:
        text.append(
            "Die Varianz steigt nicht an. Das deutet eher auf stabilisierende oder verdichtende Operatorwirkung als auf Streuung hin."
        )

    if d_shift is not None and d_shift > 0:
        text.append(
            "Die erhöhte Polaritätsverschiebung deutet auf operatorinduzierte Kippbewegungen innerhalb der relationalen Grundrichtung hin."
        )
    elif d_shift is not None:
        text.append(
            "Die Polaritätsverschiebung steigt nicht an. Operatoren erzeugen hier keine erkennbare zusätzliche Polaritätsinstabilität."
        )

    return "\n\n".join(text)


def build_report(data: Dict[str, Any], summary_df: pd.DataFrame) -> str:
    report = []

    report.append("# 6.x Operatorwirkung im FRZK-Raum unter dem Gesamtaspekt der Nachhilfe")
    report.append("")
    report.append("## 6.x.1 Einleitung")
    report.append(
        "Die Operatorvorhersage des Funktionalen Raum-Zeit-Kohärenzsystems postuliert, dass Operatoren nicht lediglich einzelne semantische Elemente verändern, "
        "sondern den gesamten Folgeraum eines Zustands umlagern. Operatoren wirken damit als funktionale Transformatoren innerhalb rekursiver Lehr-Lern-Prozesse. "
        "Unter dem Gesamtaspekt der Nachhilfe ist diese Annahme besonders relevant, weil Nachhilfe durch engmaschige Rückkopplung, wiederkehrende Interaktion "
        "und hohe diagnostische Dichte geprägt ist."
    )
    report.append("")
    report.append(
        "Die vorliegende Analyse prüft die Operatorwirkung anhand der Messgrößen semantische Dichte, Varianz, Delta-Bewegung und Polaritätsverschiebung. "
        "Ausgewertet werden drei Vergleichsräume: alle Lehrkräfte, Lehrkraft 1 und alle Lehrkräfte außer Lehrkraft 1."
    )
    report.append("")

    report.append("## 6.x.2 Modelllogische Grundlage der Operatorwirkung")
    report.append(
        "Im FRZK wird ein Bewertungssatz als rekursive Zustandsfolge im sieben-dimensionalen Bedeutungsraum modelliert. "
        "Ein Operator verändert abhängig von seinem Scope nicht nur ein isoliertes Token, sondern moduliert den folgenden semantischen Verlauf. "
        "Messbar wird diese Wirkung über die Veränderung des Zustandsvektors, über die Varianz des Folgeraums und über Polaritätswechsel."
    )
    report.append("")
    report.append("Formal kann die Operatorwirkung als Differenz zwischen operatorfreiem und operatorhaltigem Folgeraum beschrieben werden:")
    report.append("")
    report.append("`ΔO = F(V | Operator) - F(V | kein Operator)`")
    report.append("")
    report.append(
        "Ist ΔO in Dichte, Delta-Bewegung, Varianz oder Polaritätswechseln positiv ausgeprägt, spricht dies für eine funktionale Umordnung des Folgeraums."
    )
    report.append("")

    report.append("## 6.x.3 Methodik")
    report.append(
        "Die Analyse basiert auf der JSON-Datei `auswertung_04_operatorwirkung.json`. Für jeden Scope wurden operatorhaltige und operatorfreie Zustände verglichen. "
        "Die zentralen Messgrößen sind mittlere semantische Dichte, Dichtevarianz, Delta-Vektor-Norm, Polaritätsverschiebung und Operatoranteil."
    )
    report.append("")

    report.append("## 6.x.4 Ergebnisse")
    report.append("Tabelle 6.x.1: Gesamtübersicht der Operatorwirkung")
    report.append("")
    report.append(markdown_table(
        summary_df,
        [
            "gruppe",
            "n_gesamt",
            "anteil_operatorhaltig",
            "delta_mean_dichte",
            "delta_var_dichte",
            "delta_delta_vector",
            "delta_polaritaet_shift",
        ],
        [
            "Gruppe",
            "n",
            "Operatoranteil",
            "Δ Dichte",
            "Δ Varianz",
            "Δ Delta",
            "Δ Polaritätswechsel",
        ]
    ))
    report.append("")

    report.append("## 6.x.5 Interpretation der Operatorräume")
    for i, scope in enumerate(data.get("scopes", {}).keys(), start=1):
        report.append(f"### 6.x.5.{i} {SCOPES_LABELS.get(scope, scope)}")
        report.append(interpret_scope(scope, get_scope_df(data, scope, "operatorvergleich")))
        report.append("")

    report.append("## 6.x.6 Operatorwirkung als Drift- und Kohärenzmechanismus")
    report.append(
        "Operatoren sind im FRZK keine bloßen Verstärker einzelner Bewertungen. Ihre Wirkung besteht darin, die Anschlussbedingungen des Folgezustands zu verändern. "
        "Eine erhöhte Delta-Bewegung zeigt, dass der semantische Raum nach Operatoren stärker umgelagert wird. Eine erhöhte Varianz zeigt, dass der Folgeraum "
        "breiter oder instabiler wird. Eine erhöhte Polaritätsverschiebung weist darauf hin, dass Operatoren relationale Grundrichtungen wechseln lassen."
    )
    report.append("")

    report.append("## 6.x.7 Nachhilfe als regulierter Operatorraum")
    report.append(
        "Unter dem Gesamtaspekt der Nachhilfe ist entscheidend, ob Operatoren bloß Störungen erzeugen oder ob sie kontrollierte didaktische Drift ermöglichen. "
        "Nachhilfe unterscheidet sich von größeren Unterrichtssettings dadurch, dass operatorische Eingriffe schneller rückgekoppelt werden können. "
        "Ein Hinweis auf erfolgreiche Nachhilfe liegt daher nicht in maximaler Drift, sondern in regulierter Drift."
    )
    report.append("")

    report.append("## 6.x.8 Bestätigte Vorhersagen des FRZK")
    report.append("Tabelle 6.x.2: Prüfung der Operatorvorhersagen")
    report.append("")

    total_delta_move = None
    total_delta_var = None
    total_delta_shift = None

    if "delta_delta_vector" in summary_df.columns:
        s = pd.to_numeric(summary_df["delta_delta_vector"], errors="coerce").dropna()
        total_delta_move = None if s.empty else float(s.mean())

    if "delta_var_dichte" in summary_df.columns:
        s = pd.to_numeric(summary_df["delta_var_dichte"], errors="coerce").dropna()
        total_delta_var = None if s.empty else float(s.mean())

    if "delta_polaritaet_shift" in summary_df.columns:
        s = pd.to_numeric(summary_df["delta_polaritaet_shift"], errors="coerce").dropna()
        total_delta_shift = None if s.empty else float(s.mean())

    pred_df = pd.DataFrame([
        {
            "Vorhersage": "Operatoren verändern den Folgeraum",
            "Messgröße": "Δ Delta-Bewegung",
            "Ergebnis": fmt(total_delta_move),
            "Status": "bestätigt" if (total_delta_move is not None and total_delta_move > 0) else "nicht eindeutig",
        },
        {
            "Vorhersage": "Operatoren erhöhen Streuung",
            "Messgröße": "Δ Varianz",
            "Ergebnis": fmt(total_delta_var),
            "Status": "bestätigt" if (total_delta_var is not None and total_delta_var > 0) else "nicht eindeutig",
        },
        {
            "Vorhersage": "Operatoren verschieben Polarität",
            "Messgröße": "Δ Polaritätswechsel",
            "Ergebnis": fmt(total_delta_shift),
            "Status": "bestätigt" if (total_delta_shift is not None and total_delta_shift > 0) else "nicht eindeutig",
        },
    ])

    report.append(markdown_table(
        pred_df,
        ["Vorhersage", "Messgröße", "Ergebnis", "Status"],
        ["FRZK-Vorhersage", "Messgröße", "Ergebnis", "Status"]
    ))
    report.append("")

    report.append("## 6.x.9 Abbildungen")
    report.append(
        "Abbildung 6.x.1: Δ der mittleren semantischen Dichte durch Operatoren (`abb_1_operator_delta_mean_dichte.png`)."
    )
    report.append("")
    report.append(
        "Abbildung 6.x.2: Δ der Dichtevarianz durch Operatoren (`abb_2_operator_delta_varianz.png`)."
    )
    report.append("")
    report.append(
        "Abbildung 6.x.3: Δ der Delta-Bewegung durch Operatoren (`abb_3_operator_delta_bewegung.png`)."
    )
    report.append("")
    report.append(
        "Abbildung 6.x.4: Δ der Polaritätsverschiebung durch Operatoren (`abb_4_operator_delta_polaritaet.png`)."
    )
    report.append("")
    report.append(
        "Zusätzlich erzeugt das Skript für jeden Scope Detailabbildungen zu Operatorvergleich, Polaritätsgruppen, Zeitreihen, Scatterplots und dimensionalen Mittelwerten."
    )
    report.append("")

    report.append("## 6.x.10 Gesamtschlussfolgerung")
    report.append(
        "Die Operatoranalyse prüft die zentrale modelllogische Annahme des FRZK, dass Operatoren nicht nur einzelne semantische Elemente, "
        "sondern den gesamten Folgeraum verändern. Entscheidend sind daher nicht isolierte Mittelwerte, sondern Veränderungen in Delta-Bewegung, "
        "Varianz und Polaritätsstruktur. Unter dem Aspekt der Nachhilfe wird Operatorwirkung als regulierte Drift interpretierbar."
    )
    report.append("")

    return "\n".join(report)


def main() -> None:
    data = load_json()
    summary_df = build_summary(data)
    save_csv(summary_df, "gesamtuebersicht_operatorwirkung.csv")

    for scope in data.get("scopes", {}).keys():
        op_df = get_scope_df(data, scope, "operatorvergleich")
        pol_df = get_scope_df(data, scope, "polaritaet")
        time_df = get_scope_df(data, scope, "zeitreihe")
        raw_df = get_scope_df(data, scope, "rohwerte")

        save_csv(op_df, f"{scope}_operatorvergleich.csv")
        save_csv(pol_df, f"{scope}_polaritaet.csv")
        save_csv(time_df, f"{scope}_zeitreihe.csv")
        save_csv(raw_df, f"{scope}_rohwerte.csv")

        plot_operator_bar(
            op_df,
            scope,
            "mean_dichte",
            "mittlere semantische Dichte",
            "Operatorvergleich: semantische Dichte",
            f"{scope}_operatorvergleich_mean_dichte.png"
        )

        plot_operator_bar(
            op_df,
            scope,
            "var_dichte",
            "Varianz semantische Dichte",
            "Operatorvergleich: Varianz",
            f"{scope}_operatorvergleich_varianz.png"
        )

        plot_operator_bar(
            op_df,
            scope,
            "mean_delta_vector",
            "mittlere Delta-Vektor-Norm",
            "Operatorvergleich: Delta-Bewegung",
            f"{scope}_operatorvergleich_delta_vector.png"
        )

        plot_operator_bar(
            op_df,
            scope,
            "mean_polaritaet_shift",
            "mittlere Polaritätsverschiebung",
            "Operatorvergleich: Polaritätsverschiebung",
            f"{scope}_operatorvergleich_polaritaet_shift.png"
        )

        plot_polarity(pol_df, scope)
        plot_time_series(time_df, scope)
        plot_scatter(raw_df, scope)
        plot_dimension_means(raw_df, scope)

    plot_cross_scope_summary(summary_df)

    report = build_report(data, summary_df)
    (OUTDIR / "6x_operatorwirkung_frzk_bericht.md").write_text(report, encoding="utf-8")

    print(f"Auswertung vollständig erzeugt in: {OUTDIR.resolve()}")
    print("Zentrale Datei: 6x_operatorwirkung_frzk_bericht.md")


if __name__ == "__main__":
    main()