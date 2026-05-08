#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
FRZK 6.x.8 – Zeitliche Dynamik und Übergänge
Exportiert zeitlich geordnete Zustände aus dem View
`datenm_values_sem_dichte_lehrer_type_3` in ein JSON und visualisiert:

1. Zustandsbahnen der 7 FRZK-Dimensionen
2. Semantische Dichte d(S_t)
3. Übergangsstärke ||ΔS(t)||
4. Dominanzfolge d(S_t)
5. Übergangsmatrix P_ab

Optional filterbar nach:
- gruppe_id
- teilnehmer_id
- lehrkraft_id
- fach
- Zeitraum

Benötigte Pakete:
    pip install pandas numpy matplotlib mysql-connector-python
"""

from __future__ import annotations

import os
import json
import math
import argparse
from pathlib import Path
from typing import Dict, List, Any, Optional

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt

try:
    import mysql.connector
except Exception:
    mysql = None


DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DIMENSION_LABELS = [
    "Kognition",
    "Sozial",
    "Affektiv",
    "Motivation",
    "Methodik",
    "Performanz",
    "Regulation",
]

DOMINANCE_ORDER = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

DOMINANCE_TO_INDEX = {name: i for i, name in enumerate(DOMINANCE_ORDER)}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="FRZK: Zeitliche Dynamik und Übergänge aus datenm_values_sem_dichte_lehrer_type_3"
    )

    parser.add_argument("--host", default="localhost")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--database", default="icas")
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--charset", default="utf8mb4")

    parser.add_argument("--gruppe-id", type=int, default=None)
    parser.add_argument("--teilnehmer-id", type=int, default=None)
    parser.add_argument("--lehrkraft-id", type=int, default=None)
    parser.add_argument("--fach", default=None)
    parser.add_argument("--date-from", default=None, help="YYYY-MM-DD")
    parser.add_argument("--date-to", default=None, help="YYYY-MM-DD")

    parser.add_argument("--output-json", default="frzk_6x8_zeitdynamik.json")
    parser.add_argument("--output-dir", default="frzk_6x8_plots")
    parser.add_argument("--show", action="store_true")
    parser.add_argument(
        "--dominance-source",
        choices=["column", "argmax_abs"],
        default="column",
        help="column = dominante_dimension aus View verwenden; argmax_abs = aus x_* selbst bestimmen",
    )

    return parser.parse_args()


def build_query(args: argparse.Namespace) -> tuple[str, List[Any]]:
    sql = f"""
        SELECT
            id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            thema,
            bemerkung,
            wochentag,
            day_number,
            lehrkraft_id,
            id_mtr_rueckkopplung_datenmaske,
            type,
            x_kognition,
            x_sozial,
            x_affektiv,
            x_motivation,
            x_methodik,
            x_performanz,
            x_regulation,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch
        FROM datenm_values_sem_dichte_lehrer_type_3
        WHERE 1=1 and datum>='2025-09-01' and weekofyear(datum)<>44 and lehrkraft_id<>1 
    """
    params: List[Any] = []

    if args.gruppe_id is not None:
        sql += " AND gruppe_id = %s"
        params.append(args.gruppe_id)

    if args.teilnehmer_id is not None:
        sql += " AND teilnehmer_id = %s"
        params.append(args.teilnehmer_id)

    if args.lehrkraft_id is not None:
        sql += " AND lehrkraft_id = %s"
        params.append(args.lehrkraft_id)

    if args.fach:
        sql += " AND fach = %s"
        params.append(args.fach)

    if args.date_from:
        sql += " AND datum >= %s"
        params.append(args.date_from)

    if args.date_to:
        sql += " AND datum <= %s"
        params.append(args.date_to)

    sql += """
        ORDER BY
            datum ASC,
            gruppe_id ASC,
            teilnehmer_id ASC,
            id ASC
    """

    return sql, params


def load_dataframe(args: argparse.Namespace) -> pd.DataFrame:
    if mysql is None:
        raise ImportError(
            "mysql-connector-python ist nicht installiert. "
            "Installiere es mit: pip install mysql-connector-python"
        )

    sql, params = build_query(args)

    conn = mysql.connector.connect(
        host=args.host,
        port=args.port,
        database=args.database,
        user=args.user,
        password=args.password,
        charset=args.charset,
    )

    try:
        df = pd.read_sql(sql, conn, params=params)
    finally:
        conn.close()

    if df.empty:
        raise ValueError("Die Abfrage hat keine Datensätze geliefert.")

    df["datum"] = pd.to_datetime(df["datum"])
    for col in DIMENSIONS + ["d_semantisch", "dominante_dimension_wert"]:
        df[col] = pd.to_numeric(df[col], errors="coerce").fillna(0.0)

    return df


def compute_dominance_from_vector(row: pd.Series) -> str:
    vals = np.abs(row[DIMENSIONS].to_numpy(dtype=float))
    idx = int(np.argmax(vals))
    return DOMINANCE_ORDER[idx].replace("x_", "")


def prepare_states(df: pd.DataFrame, dominance_source: str) -> pd.DataFrame:
    out = df.copy()

    if dominance_source == "argmax_abs":
        out["dominance_state"] = out.apply(compute_dominance_from_vector, axis=1)
    else:
        out["dominance_state"] = out["dominante_dimension"].astype(str).str.strip().str.lower()
        mask_invalid = ~out["dominance_state"].isin(DOMINANCE_ORDER)
        if mask_invalid.any():
            out.loc[mask_invalid, "dominance_state"] = out.loc[mask_invalid].apply(
                compute_dominance_from_vector, axis=1
            )

    vectors = out[DIMENSIONS].to_numpy(dtype=float)
    deltas = np.zeros_like(vectors)
    delta_norms = np.zeros(len(out), dtype=float)

    if len(out) > 1:
        deltas[1:, :] = vectors[1:, :] - vectors[:-1, :]
        delta_norms[1:] = np.linalg.norm(deltas[1:, :], axis=1)

    out["delta_norm"] = delta_norms

    for i, dim in enumerate(DIMENSIONS):
        out[f"delta_{dim}"] = deltas[:, i]

    return out


def compute_transition_matrix(states: List[str]) -> Dict[str, Any]:
    n = len(DOMINANCE_ORDER)
    count_matrix = np.zeros((n, n), dtype=int)

    for a, b in zip(states[:-1], states[1:]):
        if a in DOMINANCE_TO_INDEX and b in DOMINANCE_TO_INDEX:
            i = DOMINANCE_TO_INDEX[a]
            j = DOMINANCE_TO_INDEX[b]
            count_matrix[i, j] += 1

    prob_matrix = np.zeros((n, n), dtype=float)
    for i in range(n):
        row_sum = count_matrix[i].sum()
        if row_sum > 0:
            prob_matrix[i, :] = count_matrix[i, :] / row_sum

    return {
        "states": DOMINANCE_ORDER,
        "counts": count_matrix.tolist(),
        "probabilities": prob_matrix.tolist(),
    }


def build_json_payload(df: pd.DataFrame, args: argparse.Namespace) -> Dict[str, Any]:
    transition = compute_transition_matrix(df["dominance_state"].tolist())

    states_json: List[Dict[str, Any]] = []
    for _, row in df.iterrows():
        state_vec = {k.replace("x_", ""): float(row[k]) for k in DIMENSIONS}
        delta_vec = {k.replace("delta_x_", ""): float(row[k]) for k in [f"delta_{d}" for d in DIMENSIONS]}

        states_json.append(
            {
                "id": int(row["id"]),
                "datum": row["datum"].strftime("%Y-%m-%d"),
                "gruppe_id": None if pd.isna(row["gruppe_id"]) else int(row["gruppe_id"]),
                "teilnehmer_id": None if pd.isna(row["teilnehmer_id"]) else int(row["teilnehmer_id"]),
                "lehrkraft_id": None if pd.isna(row["lehrkraft_id"]) else int(row["lehrkraft_id"]),
                "fach": None if pd.isna(row["fach"]) else str(row["fach"]),
                "thema": None if pd.isna(row["thema"]) else str(row["thema"]),
                "bemerkung": None if pd.isna(row["bemerkung"]) else str(row["bemerkung"]),
                "S_t": state_vec,
                "d_semantisch": float(row["d_semantisch"]),
                "dominante_dimension": str(row["dominance_state"]),
                "polaritaet_gesamt": None if pd.isna(row["polaritaet_gesamt"]) else int(row["polaritaet_gesamt"]),
                "delta_S_t": delta_vec,
                "delta_norm": float(row["delta_norm"]),
            }
        )

    payload = {
        "modell": {
            "abschnitt": "6.x.8 Zeitliche Dynamik und Übergänge",
            "definitionen": {
                "zeitlich_geordnete_zustaende": "S(t1), S(t2), ..., S(tn)",
                "differenzoperator": "ΔS(t) = S(t+1) - S(t)",
                "uebergangsfunktion": "T(S_i) = S_(i+1)",
                "uebergangsmatrix": "P_ab = P(d(S_(t+1)) = b | d(S_t) = a)",
            },
        },
        "filter": {
            "gruppe_id": args.gruppe_id,
            "teilnehmer_id": args.teilnehmer_id,
            "lehrkraft_id": args.lehrkraft_id,
            "fach": args.fach,
            "date_from": args.date_from,
            "date_to": args.date_to,
            "dominance_source": args.dominance_source,
        },
        "dimensionen": [d.replace("x_", "") for d in DIMENSIONS],
        "zeitreihe": states_json,
        "uebergangsmatrix": transition,
        "meta": {
            "n_beobachtungen": len(states_json),
            "n_uebergaenge": max(len(states_json) - 1, 0),
        },
    }

    return payload


def save_json(payload: Dict[str, Any], output_path: Path) -> None:
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with output_path.open("w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2)


def plot_state_trajectories(df: pd.DataFrame, output_dir: Path) -> Path:
    fig, ax = plt.subplots(figsize=(14, 7))
    x = np.arange(len(df))

    for dim, label in zip(DIMENSIONS, DIMENSION_LABELS):
        ax.plot(x, df[dim].to_numpy(dtype=float), marker="o", label=label)

    ax.set_title("FRZK-Zustandsbahnen S(t) über die Zeit")
    ax.set_xlabel("Zeitindex t")
    ax.set_ylabel("Dimensionswert")
    ax.grid(True, alpha=0.3)
    ax.legend()
    fig.tight_layout()

    path = output_dir / "01_zustandsbahnen.png"
    fig.savefig(path, dpi=200, bbox_inches="tight")
    plt.close(fig)
    return path


def plot_semantic_density(df: pd.DataFrame, output_dir: Path) -> Path:
    fig, ax = plt.subplots(figsize=(12, 5))
    x = np.arange(len(df))
    y = df["d_semantisch"].to_numpy(dtype=float)

    ax.plot(x, y, marker="o")
    ax.set_title("Semantische Dichte d(S_t)")
    ax.set_xlabel("Zeitindex t")
    ax.set_ylabel("d_semantisch")
    ax.grid(True, alpha=0.3)
    fig.tight_layout()

    path = output_dir / "02_semantische_dichte.png"
    fig.savefig(path, dpi=200, bbox_inches="tight")
    plt.close(fig)
    return path


def plot_delta_norm(df: pd.DataFrame, output_dir: Path) -> Path:
    fig, ax = plt.subplots(figsize=(12, 5))
    x = np.arange(len(df))
    y = df["delta_norm"].to_numpy(dtype=float)

    ax.plot(x, y, marker="o")
    ax.set_title("Übergangsstärke ||ΔS(t)||")
    ax.set_xlabel("Zeitindex t")
    ax.set_ylabel("Norm des Differenzvektors")
    ax.grid(True, alpha=0.3)
    fig.tight_layout()

    path = output_dir / "03_delta_norm.png"
    fig.savefig(path, dpi=200, bbox_inches="tight")
    plt.close(fig)
    return path


def plot_dominance_sequence(df: pd.DataFrame, output_dir: Path) -> Path:
    fig, ax = plt.subplots(figsize=(12, 4))
    x = np.arange(len(df))
    y = [DOMINANCE_TO_INDEX[s] for s in df["dominance_state"].tolist()]

    ax.step(x, y, where="mid")
    ax.set_title("Dominanzfolge d(S_t)")
    ax.set_xlabel("Zeitindex t")
    ax.set_ylabel("Dominante Dimension")
    ax.set_yticks(range(len(DOMINANCE_ORDER)))
    ax.set_yticklabels(DOMINANCE_ORDER)
    ax.grid(True, alpha=0.3)
    fig.tight_layout()

    path = output_dir / "04_dominanzfolge.png"
    fig.savefig(path, dpi=200, bbox_inches="tight")
    plt.close(fig)
    return path


def plot_transition_matrix(df: pd.DataFrame, output_dir: Path) -> Path:
    transition = compute_transition_matrix(df["dominance_state"].tolist())
    P = np.array(transition["probabilities"], dtype=float)

    fig, ax = plt.subplots(figsize=(8, 7))
    im = ax.imshow(P)

    ax.set_title("Übergangsmatrix P_ab")
    ax.set_xlabel("Folgezustand b")
    ax.set_ylabel("Ausgangszustand a")
    ax.set_xticks(range(len(DOMINANCE_ORDER)))
    ax.set_yticks(range(len(DOMINANCE_ORDER)))
    ax.set_xticklabels(DOMINANCE_ORDER, rotation=45, ha="right")
    ax.set_yticklabels(DOMINANCE_ORDER)

    for i in range(P.shape[0]):
        for j in range(P.shape[1]):
            ax.text(j, i, f"{P[i, j]:.2f}", ha="center", va="center")

    fig.colorbar(im, ax=ax, label="P_ab")
    fig.tight_layout()

    path = output_dir / "05_uebergangsmatrix.png"
    fig.savefig(path, dpi=200, bbox_inches="tight")
    plt.close(fig)
    return path


def main() -> None:
    args = parse_args()
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    df = load_dataframe(args)
    df = prepare_states(df, dominance_source=args.dominance_source)

    payload = build_json_payload(df, args)
    save_json(payload, Path(args.output_json))

    created = [
        plot_state_trajectories(df, output_dir),
        plot_semantic_density(df, output_dir),
        plot_delta_norm(df, output_dir),
        plot_dominance_sequence(df, output_dir),
        plot_transition_matrix(df, output_dir),
    ]

    print(f"JSON exportiert nach: {Path(args.output_json).resolve()}")
    print("Plots:")
    for p in created:
        print(f" - {p.resolve()}")

    if args.show:
        # separate re-open with default backend if interactive display is desired
        try:
            import matplotlib.image as mpimg

            for p in created:
                img = mpimg.imread(p)
                plt.figure(figsize=(12, 6))
                plt.imshow(img)
                plt.axis("off")
                plt.title(p.name)
            plt.show()
        except Exception as exc:
            print(f"Hinweis: Interaktive Anzeige fehlgeschlagen: {exc}")


if __name__ == "__main__":
    main()
