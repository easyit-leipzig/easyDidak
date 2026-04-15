import json
from pathlib import Path
from typing import Dict, List, Tuple

import numpy as np
import pandas as pd


DIMS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def load_json_dataset(path: str | Path) -> dict:
    path = Path(path)
    with path.open("r", encoding="utf-8") as f:
        return json.load(f)


def raw_to_dataframe(payload: dict) -> pd.DataFrame:
    df = pd.DataFrame(payload["rohdaten"]).copy()
    missing = [d for d in DIMS if d not in df.columns]
    if missing:
        raise ValueError(f"Fehlende Dimensionen in Rohdaten: {missing}")
    return df


def transform_relative_to_centroid(
    df: pd.DataFrame,
    alpha: float = 10.0,
    dims: List[str] = DIMS,
) -> Tuple[pd.DataFrame, pd.Series]:
    """
    FRZK-konforme metrische Streckung:
        S_i' = S_bar + alpha * (S_i - S_bar)

    Der Schwerpunkt bleibt invariant, die Unterschiede werden sichtbar gemacht.
    """
    if alpha <= 0:
        raise ValueError("alpha muss > 0 sein.")

    out = df.copy()
    centroid = out[dims].mean()
    out[dims] = centroid + alpha * (out[dims] - centroid)
    return out, centroid


def compute_profile(df: pd.DataFrame, dims: List[str] = DIMS) -> Dict[str, float]:
    X = df[dims].to_numpy(float)
    centroid = df[dims].mean().to_numpy(float)
    sq_dist = ((X - centroid) ** 2).sum(axis=1)
    return {
        "n": int(len(df)),
        "mean_dist_to_centroid": float(np.sqrt(sq_dist).mean()),
        "var_state_total": float(sq_dist.mean()),
        **{f"mean_{d}": float(df[d].mean()) for d in dims},
        **{f"var_{d}": float(df[d].var(ddof=0)) for d in dims},
    }


def save_transformed_json(
    input_path: str | Path,
    output_path: str | Path,
    alpha: float = 10.0,
) -> None:
    payload = load_json_dataset(input_path)
    df = raw_to_dataframe(payload)

    transformed, centroid = transform_relative_to_centroid(df, alpha=alpha)

    out_payload = {
        "meta": {
            **payload.get("meta", {}),
            "transformation": {
                "type": "centroid_scaling",
                "alpha": float(alpha),
                "formula": "S_i' = S_bar + alpha * (S_i - S_bar)",
            },
        },
        "centroid_original": {k: float(v) for k, v in centroid.items()},
        "profile_original": compute_profile(df),
        "profile_transformed": compute_profile(transformed),
        "rohdaten_transformiert": transformed.to_dict(orient="records"),
    }

    output_path = Path(output_path)
    with output_path.open("w", encoding="utf-8") as f:
        json.dump(out_payload, f, ensure_ascii=False, indent=2)


if __name__ == "__main__":
    save_transformed_json("sem_dichte_auswertung.json", "alle_alpha10.json", alpha=10.0)
    save_transformed_json("sem_dichte_auswertung - nicht 1.json", "nicht1_alpha10.json", alpha=10.0)
    save_transformed_json("sem_dichte_auswertung - 1.json", "lehrkraft1_alpha10.json", alpha=10.0)
    print("Transformierte JSON-Dateien wurden erzeugt.")
