from pathlib import Path
import json
import argparse
import pandas as pd
import matplotlib.pyplot as plt

DIMS = ["x_kognition", "x_methodik", "x_performanz"]
LABELS = {
    "x_kognition": "Kognition",
    "x_methodik": "Methodik",
    "x_performanz": "Performanz",
}
FILES = {
    "ALL": "korrelationsstruktur_lehrkraftsicht_alle.json",
    "Lehrkraft_1": "korrelationsstruktur_lehrkraftsicht_1.json",
    "nicht_Lehrkraft_1": "korrelationsstruktur_lehrkraftsicht_nicht1.json",
}

def main() -> None:
    parser = argparse.ArgumentParser(description="Erzeugt 3D-FRZK-Grafiken mit echten Dimensionen.")
    parser.add_argument("--input-dir", default=".", help="Ordner mit den JSON-Dateien")
    parser.add_argument("--output-dir", default="./frzk_3d_echte_dimensionen", help="Ausgabeordner für die PNG-Dateien")
    args = parser.parse_args()

    input_dir = Path(args.input_dir)
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    for name, filename in FILES.items():
        path = input_dir / filename
        with path.open("r", encoding="utf-8") as f:
            payload = json.load(f)

        df = pd.DataFrame(payload["datenbasis"])[DIMS].astype(float)

        fig = plt.figure(figsize=(7, 6))
        ax = fig.add_subplot(111, projection="3d")
        ax.scatter(df["x_kognition"], df["x_methodik"], df["x_performanz"], s=14, alpha=0.75)
        ax.set_xlabel(LABELS["x_kognition"])
        ax.set_ylabel(LABELS["x_methodik"])
        ax.set_zlabel(LABELS["x_performanz"])
        ax.set_title(f"3D-FRZK-Zustandsraum – {name}")

        out = output_dir / f"grafik_stabilitaet_3d_echt_{name}.png"
        fig.savefig(out, dpi=300, bbox_inches="tight")
        plt.close(fig)

    print(f"Fertig. Dateien liegen in: {output_dir}")

if __name__ == "__main__":
    main()
