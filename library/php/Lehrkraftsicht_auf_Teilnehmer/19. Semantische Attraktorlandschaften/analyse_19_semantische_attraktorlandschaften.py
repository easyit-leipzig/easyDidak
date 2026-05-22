# -*- coding: utf-8 -*-
"""
Analyse 19: Semantische Attraktorlandschaften
Liest die JSON-Datei aus export_19_semantische_attraktorlandschaften.py und erzeugt
textuelle sowie grafische Auswertungen.

Ergebnisse:
- summary_19_semantische_attraktorlandschaften.txt
- CSV-Dateien mit Cluster-/Übergangs-/Kipppunktdaten
- PNG-Charts pro Scope
"""

import json
import math
from pathlib import Path
from typing import Dict, List, Tuple

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt

try:
    from sklearn.cluster import KMeans
    from sklearn.decomposition import PCA
    from sklearn.metrics import silhouette_score
    from sklearn.preprocessing import StandardScaler
except ImportError as exc:
    raise SystemExit("Bitte installieren: pip install pandas numpy matplotlib scikit-learn") from exc

INFILE = Path("auswertung_19_semantische_attraktorlandschaften.json")
OUTDIR = Path("auswertung_19_semantische_attraktorlandschaften_output")
OUTDIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]
XCOLS = [f"x_{d}" for d in DIMENSIONS]
SUMCOLS = [f"sum_{d}" for d in DIMENSIONS]

RANDOM_STATE = 42
MAX_K = 8
MIN_K = 2


def safe_name(name: str) -> str:
    return name.replace(" ", "_").replace("/", "_").lower()


def load_data() -> Dict:
    if not INFILE.exists():
        raise FileNotFoundError(f"JSON-Datei nicht gefunden: {INFILE}")
    return json.loads(INFILE.read_text(encoding="utf-8"))


def records_to_df(records: List[Dict]) -> pd.DataFrame:
    df = pd.DataFrame(records)
    if df.empty:
        return df
    for col in XCOLS + SUMCOLS + ["d_semantisch", "dominante_dimension_wert", "polaritaet_gesamt"]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    for col in ["dat_ges", "datum", "created_at"]:
        if col in df.columns:
            df[col] = pd.to_datetime(df[col], errors="coerce")
    sort_cols = [c for c in ["lehrkraft_id", "gruppe_id", "teilnehmer_id", "dat_ges", "ue_id", "id"] if c in df.columns]
    if sort_cols:
        df = df.sort_values(sort_cols).reset_index(drop=True)
    return df


def feature_matrix(df: pd.DataFrame) -> Tuple[np.ndarray, List[str]]:
    cols = [c for c in SUMCOLS if c in df.columns and df[c].notna().any()]
    if len(cols) < 2:
        cols = [c for c in XCOLS if c in df.columns and df[c].notna().any()]
    if len(cols) < 2:
        raise ValueError("Zu wenige numerische FRZK-Dimensionen für Attraktorlandschaft.")
    X = df[cols].fillna(0.0).to_numpy(dtype=float)
    return X, cols


def choose_k(Xs: np.ndarray) -> int:
    n = len(Xs)
    if n < 4:
        return 1
    best_k, best_score = 2, -1.0
    upper = min(MAX_K, n - 1)
    for k in range(MIN_K, upper + 1):
        labels = KMeans(n_clusters=k, n_init=20, random_state=RANDOM_STATE).fit_predict(Xs)
        if len(set(labels)) < 2:
            continue
        score = silhouette_score(Xs, labels)
        if score > best_score:
            best_k, best_score = k, score
    return best_k


def cluster_states(df: pd.DataFrame) -> Tuple[pd.DataFrame, pd.DataFrame, PCA, np.ndarray, List[str]]:
    X, cols = feature_matrix(df)
    scaler = StandardScaler()
    Xs = scaler.fit_transform(X)
    k = choose_k(Xs)
    if k == 1:
        labels = np.zeros(len(df), dtype=int)
    else:
        labels = KMeans(n_clusters=k, n_init=50, random_state=RANDOM_STATE).fit_predict(Xs)
    pca = PCA(n_components=2, random_state=RANDOM_STATE)
    XY = pca.fit_transform(Xs)
    out = df.copy()
    out["cluster"] = labels
    out["pca_x"] = XY[:, 0]
    out["pca_y"] = XY[:, 1]

    centroids = []
    for cl in sorted(out["cluster"].unique()):
        sub = out[out["cluster"] == cl]
        c = {
            "cluster": int(cl),
            "n": int(len(sub)),
            "anteil": float(len(sub) / len(out)),
            "pca_x": float(sub["pca_x"].mean()),
            "pca_y": float(sub["pca_y"].mean()),
            "d_semantisch_mean": float(sub.get("d_semantisch", pd.Series(dtype=float)).mean()) if "d_semantisch" in sub else math.nan,
            "polaritaet_mean": float(sub.get("polaritaet_gesamt", pd.Series(dtype=float)).mean()) if "polaritaet_gesamt" in sub else math.nan,
            "dominante_dimension_modus": str(sub["dominante_dimension"].mode().iloc[0]) if "dominante_dimension" in sub and not sub["dominante_dimension"].mode().empty else "",
        }
        for col in cols:
            c[f"{col}_mean"] = float(sub[col].mean())
        centroids.append(c)
    basin_df = pd.DataFrame(centroids)
    return out, basin_df, pca, Xs, cols


def transition_analysis(df: pd.DataFrame) -> Tuple[pd.DataFrame, pd.DataFrame]:
    group_cols = [c for c in ["lehrkraft_id", "gruppe_id", "teilnehmer_id"] if c in df.columns]
    transitions = []
    dwell = []
    for _, g in df.groupby(group_cols, dropna=False) if group_cols else [(None, df)]:
        g = g.sort_values([c for c in ["dat_ges", "ue_id", "id"] if c in g.columns])
        labels = g["cluster"].to_list()
        if not labels:
            continue
        run_cluster, run_len = labels[0], 1
        for a, b in zip(labels[:-1], labels[1:]):
            transitions.append({"from_cluster": int(a), "to_cluster": int(b), "count": 1})
            if b == run_cluster:
                run_len += 1
            else:
                dwell.append({"cluster": int(run_cluster), "dwell_length": int(run_len)})
                run_cluster, run_len = b, 1
        dwell.append({"cluster": int(run_cluster), "dwell_length": int(run_len)})
    if transitions:
        trans = pd.DataFrame(transitions).groupby(["from_cluster", "to_cluster"], as_index=False)["count"].sum()
        trans["probability"] = trans["count"] / trans.groupby("from_cluster")["count"].transform("sum")
    else:
        trans = pd.DataFrame(columns=["from_cluster", "to_cluster", "count", "probability"])
    dwell_df = pd.DataFrame(dwell) if dwell else pd.DataFrame(columns=["cluster", "dwell_length"])
    return trans, dwell_df


def tipping_points(df: pd.DataFrame) -> pd.DataFrame:
    rows = []
    group_cols = [c for c in ["lehrkraft_id", "gruppe_id", "teilnehmer_id"] if c in df.columns]
    for _, g in df.groupby(group_cols, dropna=False) if group_cols else [(None, df)]:
        g = g.sort_values([c for c in ["dat_ges", "ue_id", "id"] if c in g.columns]).reset_index(drop=True)
        if len(g) < 2:
            continue
        V = g[[c for c in SUMCOLS if c in g.columns]].fillna(0.0).to_numpy(dtype=float)
        if V.shape[1] < 2:
            V = g[[c for c in XCOLS if c in g.columns]].fillna(0.0).to_numpy(dtype=float)
        diffs = np.linalg.norm(np.diff(V, axis=0), axis=1)
        threshold = float(np.nanmean(diffs) + np.nanstd(diffs)) if len(diffs) else math.inf
        for i, dist in enumerate(diffs, start=1):
            prev = g.iloc[i - 1]
            cur = g.iloc[i]
            pol_change = int(prev.get("polaritaet_gesamt", 0) != cur.get("polaritaet_gesamt", 0))
            dim_change = int(prev.get("dominante_dimension", "") != cur.get("dominante_dimension", ""))
            cluster_change = int(prev.get("cluster", -1) != cur.get("cluster", -1))
            is_tip = dist >= threshold or pol_change or dim_change
            if is_tip:
                rows.append({
                    "id": cur.get("id"),
                    "zeitpunkt": cur.get("dat_ges"),
                    "lehrkraft_id": cur.get("lehrkraft_id"),
                    "gruppe_id": cur.get("gruppe_id"),
                    "teilnehmer_id": cur.get("teilnehmer_id"),
                    "from_cluster": int(prev.get("cluster", -1)),
                    "to_cluster": int(cur.get("cluster", -1)),
                    "delta_norm": float(dist),
                    "threshold": threshold,
                    "cluster_change": cluster_change,
                    "polaritaetswechsel": pol_change,
                    "dominanzwechsel": dim_change,
                    "dominante_dimension_alt": prev.get("dominante_dimension", ""),
                    "dominante_dimension_neu": cur.get("dominante_dimension", ""),
                })
    return pd.DataFrame(rows)


def plot_landscape(df: pd.DataFrame, basin_df: pd.DataFrame, scope: str) -> None:
    name = safe_name(scope)
    x = df["pca_x"].to_numpy()
    y = df["pca_y"].to_numpy()
    if len(df) < 3:
        return
    gx, gy = np.meshgrid(
        np.linspace(x.min() - 0.5, x.max() + 0.5, 80),
        np.linspace(y.min() - 0.5, y.max() + 0.5, 80),
    )
    z = np.zeros_like(gx)
    bw = max(np.std(x), np.std(y), 0.2) * 0.45
    for xi, yi in zip(x, y):
        z += np.exp(-((gx - xi) ** 2 + (gy - yi) ** 2) / (2 * bw ** 2))
    potential = -z

    plt.figure(figsize=(10, 8))
    plt.contourf(gx, gy, potential, levels=20, alpha=0.85)
    plt.scatter(x, y, s=25)
    plt.scatter(basin_df["pca_x"], basin_df["pca_y"], s=180, marker="X")
    for _, r in basin_df.iterrows():
        plt.text(r["pca_x"], r["pca_y"], f"A{int(r['cluster'])}", fontsize=11)
    plt.title(f"Semantische Potential-/Attraktorlandschaft – {scope}")
    plt.xlabel("PCA 1")
    plt.ylabel("PCA 2")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"{name}_01_potentialfeld_attraktorlandschaft.png", dpi=180)
    plt.close()

    plt.figure(figsize=(10, 8))
    plt.scatter(x, y, c=df["cluster"], s=35)
    for _, g in df.groupby([c for c in ["lehrkraft_id", "gruppe_id", "teilnehmer_id"] if c in df.columns], dropna=False):
        g = g.sort_values([c for c in ["dat_ges", "ue_id", "id"] if c in g.columns])
        plt.plot(g["pca_x"], g["pca_y"], linewidth=0.8, alpha=0.45)
    plt.title(f"Trajektorienfeld im FRZK-Raum – {scope}")
    plt.xlabel("PCA 1")
    plt.ylabel("PCA 2")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"{name}_02_trajektorienfeld.png", dpi=180)
    plt.close()


def plot_transition_heatmap(trans: pd.DataFrame, scope: str) -> None:
    if trans.empty:
        return
    name = safe_name(scope)
    labels = sorted(set(trans["from_cluster"]).union(set(trans["to_cluster"])))
    matrix = pd.DataFrame(0.0, index=labels, columns=labels)
    for _, r in trans.iterrows():
        matrix.loc[int(r["from_cluster"]), int(r["to_cluster"])] = r["probability"]
    plt.figure(figsize=(8, 7))
    plt.imshow(matrix.to_numpy(), aspect="auto")
    plt.xticks(range(len(labels)), labels)
    plt.yticks(range(len(labels)), labels)
    plt.colorbar(label="Übergangswahrscheinlichkeit")
    plt.title(f"Übergangswahrscheinlichkeiten zwischen Attraktoren – {scope}")
    plt.xlabel("Ziel-Attraktor")
    plt.ylabel("Ausgangs-Attraktor")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"{name}_03_transition_heatmap.png", dpi=180)
    plt.close()


def plot_basin_bar(basin_df: pd.DataFrame, scope: str) -> None:
    if basin_df.empty:
        return
    name = safe_name(scope)
    plt.figure(figsize=(9, 5))
    plt.bar(basin_df["cluster"].astype(str), basin_df["anteil"])
    plt.title(f"Attraktorbecken nach Anteil der Zustände – {scope}")
    plt.xlabel("Attraktor / Cluster")
    plt.ylabel("Anteil")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"{name}_04_attraktorbecken_anteile.png", dpi=180)
    plt.close()


def plot_tipping_points(df: pd.DataFrame, tips: pd.DataFrame, scope: str) -> None:
    if df.empty:
        return
    name = safe_name(scope)
    ordered = df.sort_values([c for c in ["dat_ges", "ue_id", "id"] if c in df.columns]).reset_index(drop=True)
    plt.figure(figsize=(11, 5))
    plt.plot(range(len(ordered)), ordered.get("d_semantisch", pd.Series([np.nan] * len(ordered))))
    if not tips.empty and "id" in ordered.columns:
        tip_ids = set(tips["id"].dropna().tolist())
        idx = [i for i, r in ordered.iterrows() if r.get("id") in tip_ids]
        if idx:
            plt.scatter(idx, ordered.loc[idx, "d_semantisch"], s=80, marker="x")
    plt.title(f"Kipppunktzonen über semantische Dichte – {scope}")
    plt.xlabel("zeitlich geordnete Zustände")
    plt.ylabel("d_semantisch")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"{name}_05_kipppunktzonen.png", dpi=180)
    plt.close()


def textual_summary(scope: str, df: pd.DataFrame, basin: pd.DataFrame, trans: pd.DataFrame, dwell: pd.DataFrame, tips: pd.DataFrame) -> str:
    if df.empty:
        return f"\n{scope}\nKeine Datensätze vorhanden.\n"
    metastable = []
    if not trans.empty:
        self_probs = trans[trans["from_cluster"] == trans["to_cluster"]].copy()
        for _, r in self_probs.iterrows():
            cl = int(r["from_cluster"])
            mean_dwell = dwell[dwell["cluster"] == cl]["dwell_length"].mean() if not dwell.empty else 0
            if r["probability"] >= 0.50 or mean_dwell >= 2.0:
                metastable.append((cl, float(r["probability"]), float(mean_dwell)))
    dominant = basin.sort_values("anteil", ascending=False).head(3)
    lines = [
        f"\n{scope}",
        f"Datensätze: {len(df)}",
        f"Attraktoren/Cluster: {df['cluster'].nunique()}",
        f"Kipppunktzonen: {len(tips)}",
        "Dominante Attraktorbecken:",
    ]
    for _, r in dominant.iterrows():
        lines.append(
            f"  - A{int(r['cluster'])}: n={int(r['n'])}, Anteil={r['anteil']:.3f}, "
            f"dominante Dimension={r.get('dominante_dimension_modus','')}, mittlere Dichte={r.get('d_semantisch_mean', np.nan):.3f}"
        )
    if metastable:
        lines.append("Metastabile Räume:")
        for cl, p, dwell_len in metastable:
            lines.append(f"  - A{cl}: Selbstübergang p={p:.3f}, mittlere Verweildauer={dwell_len:.2f}")
    else:
        lines.append("Metastabile Räume: keine robuste Selbstbindung nach Schwellenwert erkannt.")
    if not trans.empty:
        top_trans = trans[trans["from_cluster"] != trans["to_cluster"]].sort_values("probability", ascending=False).head(5)
        lines.append("Stärkste Übergänge:")
        for _, r in top_trans.iterrows():
            lines.append(f"  - A{int(r['from_cluster'])} → A{int(r['to_cluster'])}: p={r['probability']:.3f}, n={int(r['count'])}")
    return "\n".join(lines) + "\n"


def analyze_scope(scope: str, scope_data: Dict) -> str:
    df = records_to_df(scope_data.get("records", []))
    if len(df) < 3:
        return f"\n{scope}\nZu wenige Datensätze für eine Attraktorlandschaft: n={len(df)}\n"
    clustered, basin, _, _, _ = cluster_states(df)
    trans, dwell = transition_analysis(clustered)
    tips = tipping_points(clustered)

    prefix = safe_name(scope)
    clustered.to_csv(OUTDIR / f"{prefix}_states_with_clusters.csv", index=False, encoding="utf-8-sig")
    basin.to_csv(OUTDIR / f"{prefix}_attraktorbecken.csv", index=False, encoding="utf-8-sig")
    trans.to_csv(OUTDIR / f"{prefix}_transition_probabilities.csv", index=False, encoding="utf-8-sig")
    dwell.to_csv(OUTDIR / f"{prefix}_dwell_times.csv", index=False, encoding="utf-8-sig")
    tips.to_csv(OUTDIR / f"{prefix}_kipppunktzonen.csv", index=False, encoding="utf-8-sig")

    plot_landscape(clustered, basin, scope)
    plot_transition_heatmap(trans, scope)
    plot_basin_bar(basin, scope)
    plot_tipping_points(clustered, tips, scope)

    return textual_summary(scope, clustered, basin, trans, dwell, tips)


def main() -> None:
    data = load_data()
    summaries = [
        "Auswertung 19 – Semantische Attraktorlandschaften",
        f"Quelle: {data.get('quelle', '')}",
        f"Erzeugt aus: {INFILE}",
        ""
    ]
    for scope, scope_data in data.get("scopes", {}).items():
        summaries.append(analyze_scope(scope, scope_data))
    summary_text = "\n".join(summaries)
    (OUTDIR / "summary_19_semantische_attraktorlandschaften.txt").write_text(summary_text, encoding="utf-8")
    print(summary_text)
    print(f"\nAusgaben gespeichert in: {OUTDIR.resolve()}")


if __name__ == "__main__":
    main()
