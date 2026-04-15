from pathlib import Path
import argparse
import numpy as np
import matplotlib.pyplot as plt

STRENGTHS = {
    "ALL": 0.386,
    "Lehrkraft_1": 0.476,
    "nicht_Lehrkraft_1": 0.310,
}
STARTS = np.array([
    [-2.0, -1.8],
    [-2.1,  1.7],
    [-1.4,  0.8],
    [-0.8, -2.1],
    [ 0.9,  1.9],
    [ 1.7, -1.6],
    [ 2.0,  1.3],
    [ 1.5, -0.4],
], dtype=float)

def amp(s: float) -> float:
    return (s ** 2) * 5.0

def make_operator(s: float) -> np.ndarray:
    a = amp(s)
    return np.array([
        [1 - (0.2 + a), -0.25],
        [0.25, 1 - (0.2 + a)]
    ], dtype=float)

def simulate_norms(A: np.ndarray, starts: np.ndarray, n_steps: int) -> np.ndarray:
    rows = []
    for p0 in starts:
        p = p0.copy()
        norms = [float(np.linalg.norm(p))]
        for _ in range(n_steps):
            p = A @ p
            norms.append(float(np.linalg.norm(p)))
        rows.append(norms)
    return np.array(rows)

def main() -> None:
    parser = argparse.ArgumentParser(description="Erzeugt quantitative Konvergenzkurven.")
    parser.add_argument("--output-dir", default="./frzk_konvergenzkurven", help="Ausgabeordner")
    parser.add_argument("--steps", type=int, default=16, help="Anzahl der Iterationsschritte")
    args = parser.parse_args()

    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    for name, s in STRENGTHS.items():
        A = make_operator(s)
        norms = simulate_norms(A, STARTS, args.steps)
        mean_norm = norms.mean(axis=0)

        fig, ax = plt.subplots(figsize=(7, 5))
        for row in norms:
            ax.plot(range(args.steps + 1), row, alpha=0.6, linewidth=1.5)
        ax.plot(range(args.steps + 1), mean_norm, linewidth=3)

        ax.set_xlabel("Iterationsschritt t")
        ax.set_ylabel("Norm ||S_t||")
        ax.set_title(f"Quantitative Konvergenzkurve – {name}")
        fig.tight_layout()

        out = output_dir / f"grafik_konvergenz_{name}.png"
        fig.savefig(out, dpi=300, bbox_inches="tight")
        plt.close(fig)

    fig, ax = plt.subplots(figsize=(7, 5))
    for name, s in STRENGTHS.items():
        A = make_operator(s)
        norms = simulate_norms(A, STARTS, args.steps)
        mean_norm = norms.mean(axis=0)
        ax.plot(range(args.steps + 1), mean_norm, linewidth=2, label=name)

    ax.set_xlabel("Iterationsschritt t")
    ax.set_ylabel("mittlere Norm ||S_t||")
    ax.set_title("Vergleich der quantitativen Konvergenz")
    ax.legend()
    fig.tight_layout()

    out = output_dir / "grafik_konvergenz_vergleich.png"
    fig.savefig(out, dpi=300, bbox_inches="tight")
    plt.close(fig)

    print(f"Fertig. Dateien liegen in: {output_dir}")

if __name__ == "__main__":
    main()
