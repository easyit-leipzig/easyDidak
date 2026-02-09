#!/usr/bin/env python3
# frzk_semantic_density_sim.py
# Visualisierung der semantischen Dichtefunktion h(x,y,z) (Kap. 6.1.3 FRZK)
# und einfache Simulation von Lernendenbewegungen (U_t).
#
# Usage:
#   python frzk_semantic_density_sim.py         # zeigt interaktiven Plot (matplotlib)
#   python frzk_semantic_density_sim.py --save  # speichert PNGs in ./output_frzk/
#
# Requirements: numpy, matplotlib
# Install (if needed): pip install numpy matplotlib

import os
import argparse
import numpy as np
import matplotlib.pyplot as plt
from mpl_toolkits.mplot3d import Axes3D  # registers 3D projection
from matplotlib import cm

# ---------------------------
# --- Configuration area ---
# ---------------------------
# Domain bounds (x,y,z) for sampling and visualization
BOUNDS = (-5.0, 5.0)
N_SAMP_AXIS = 26         # number of sample points per axis for 3D scatter (coarser -> faster)
N_SLICE_RES = 200        # resolution for contour slices (XY)
Z_SLICES = [-2.0, 0.0, 2.0]

# Hubs: list of (position numpy array (3,), weight w_k, sigma_k)
DEFAULT_HUBS = [
    (np.array([-1.5,  0.0,  1.0]), 1.8, 0.9),
    (np.array([ 2.0,  1.5, -0.5]), 1.6, 0.7),
    (np.array([ 0.5, -2.0,  0.0]), 1.2, 1.1),
    (np.array([-2.5,  2.5, -2.0]), 0.9, 0.8),
]

# Simulation parameters for moving learners
SIMULATE_LEARNERS = True
N_LEARNERS = 12
ALPHA = 0.25   # strength of oriented movement towards hubs
BETA = 0.12    # strength of random/drift component
N_STEPS = 60   # simulation steps
SEED = 42

# Output directory when --save
OUT_DIR = "output_frzk"

# ---------------------------
# --- Helper functions ---
# ---------------------------
def semantic_density(U, hubs):
    """
    Compute h(U) = sum_k w_k * exp(-||U - h_k||^2 / (2 sigma_k^2))
    U: array shape (..., 3) or (3,)
    hubs: iterable of (pos (3,), w, sigma)
    Returns: array of shape (N,) or scalar if input single point
    """
    U = np.asarray(U)
    single = (U.ndim == 1 and U.shape[0] == 3)
    if single:
        U = U.reshape((1, 3))
    H = np.zeros(U.shape[0], dtype=float)
    for pos, w, sigma in hubs:
        diff = U - pos.reshape((1, 3))
        r2 = np.sum(diff * diff, axis=1)
        H += w * np.exp(- r2 / (2.0 * sigma**2))
    if single:
        return H[0]
    return H

def orientation_O(U, hubs):
    """Alias to semantic_density: orientation measure O(U) in the FRZK text"""
    return semantic_density(U, hubs)

def unit_vector(vec):
    n = np.linalg.norm(vec)
    return vec / n if n > 1e-9 else vec

# ---------------------------
# --- Plotting functions ---
# ---------------------------
def plot_3d_scatter_density(hubs, bounds=BOUNDS, n_axis=N_SAMP_AXIS, show=True, savepath=None):
    """3D scatter sample colored by semantic density"""
    lin = np.linspace(bounds[0], bounds[1], n_axis)
    X, Y, Z = np.meshgrid(lin, lin, lin, indexing="xy")
    pts = np.column_stack([X.ravel(), Y.ravel(), Z.ravel()])
    h_vals = semantic_density(pts, hubs)

    # create figure
    fig = plt.figure(figsize=(10, 8))
    ax = fig.add_subplot(111, projection='3d')
    sc = ax.scatter(pts[:,0], pts[:,1], pts[:,2], c=h_vals, s=10, marker='o', depthshade=True)

    # mark hubs
    hub_positions = np.array([h[0] for h in hubs])
    ax.scatter(hub_positions[:,0], hub_positions[:,1], hub_positions[:,2], s=140, marker='X', edgecolor='k')

    ax.set_xlabel("x (kognitiv)")
    ax.set_ylabel("y (sozial)")
    ax.set_zlabel("z (affektiv)")
    ax.set_title("Semantische Dichte h(x,y,z) – 3D Sample Scatter (FRZK §6.1.3)")
    cbar = fig.colorbar(sc, ax=ax, shrink=0.6, pad=0.08)
    cbar.set_label("h (epistemische Dichte)")
    fig.tight_layout()

    if savepath:
        fig.savefig(savepath, dpi=180)
        print("Saved:", savepath)
    if show:
        plt.show()
    plt.close(fig)

def plot_xy_slices(hubs, z_slices=Z_SLICES, bounds=BOUNDS, res=N_SLICE_RES, show=True, out_prefix=None):
    """Plot contourf slices in XY plane at chosen z values"""
    x = np.linspace(bounds[0], bounds[1], res)
    y = np.linspace(bounds[0], bounds[1], res)
    XX, YY = np.meshgrid(x, y, indexing='xy')

    for z0 in z_slices:
        pts = np.column_stack([XX.ravel(), YY.ravel(), np.full(XX.size, z0)])
        H = semantic_density(pts, hubs).reshape(XX.shape)

        fig, ax = plt.subplots(figsize=(7,6))
        cf = ax.contourf(XX, YY, H, levels=30)
        ax.set_title(f"Semantische Dichte h(x,y,z={z0:.1f}) — XY Slice")
        ax.set_xlabel("x (kognitiv)")
        ax.set_ylabel("y (sozial)")

        # project hubs onto slice
        for pos, w, sigma in hubs:
            ax.scatter(pos[0], pos[1], s=50, marker='o')

        cbar = fig.colorbar(cf, ax=ax)
        cbar.set_label("h (epistemische Dichte)")
        fig.tight_layout()

        if out_prefix:
            fname = f"{out_prefix}_xy_slice_z{z0:.1f}.png".replace(" ", "").replace("+", "p").replace("-", "m")
            fig.savefig(fname, dpi=180)
            print("Saved:", fname)
        if show:
            plt.show()
        plt.close(fig)

def simulate_learners_and_plot(hubs, n_learners=N_LEARNERS, n_steps=N_STEPS,
                               alpha=ALPHA, beta=BETA, bounds=BOUNDS, show=True, savepath=None, seed=SEED):
    """
    Simulate simple learner dynamics:
    U_{t+1} = U_t + alpha * O(U_t) * d_hub + beta * (1-O(U_t)) * random_noise
    where d_hub points towards weighted average of hubs (direction of attraction).
    """
    rng = np.random.default_rng(seed)

    # initialize learners randomly in domain
    learners = rng.uniform(bounds[0], bounds[1], size=(n_learners, 3))
    traj = np.zeros((n_steps+1, n_learners, 3))
    traj[0] = learners

    for t in range(n_steps):
        for i in range(n_learners):
            U = traj[t, i]
            O = orientation_O(U, hubs)

            # compute direction to hubs: weighted vector towards weighted average of hubs by their contribution at U
            contribs = []
            for pos, w, sigma in hubs:
                r2 = np.sum((U - pos)**2)
                contrib = w * np.exp(- r2 / (2.0 * sigma**2))
                contribs.append(contrib)
            contribs = np.array(contribs)

            if contribs.sum() > 1e-9:
                weighted_center = sum(pos * c for (pos, _, _), c in zip(hubs, contribs)) / contribs.sum()
                d_hub = weighted_center - U
                d_dir = unit_vector(d_hub)
            else:
                d_dir = rng.normal(size=3)
                d_dir = unit_vector(d_dir)

            # stochastic component
            noise = rng.normal(scale=1.0, size=3)
            noise = unit_vector(noise)
            delta = alpha * O * d_dir + beta * (1 - O) * noise
            traj[t+1, i] = traj[t, i] + delta

    # Plot trajectories in 3D
    fig = plt.figure(figsize=(10, 8))
    ax = fig.add_subplot(111, projection='3d')

    # plot hubs
    hub_positions = np.array([h[0] for h in hubs])
    ax.scatter(hub_positions[:,0], hub_positions[:,1], hub_positions[:,2], s=160, marker='X', edgecolor='k')

    # plot each learner path
    for i in range(n_learners):
        path = traj[:, i, :]
        ax.plot(path[:,0], path[:,1], path[:,2], linewidth=1.5)
        ax.scatter(path[-1,0], path[-1,1], path[-1,2], s=30)  # final position

    ax.set_xlabel("x (kognitiv)")
    ax.set_ylabel("y (sozial)")
    ax.set_zlabel("z (affektiv)")
    ax.set_title("Simulation: Lernendenpfade im epistemischen Raum (FRZK Dynamik)")
    fig.tight_layout()

    if savepath:
        fig.savefig(savepath, dpi=180)
        print("Saved:", savepath)
    if show:
        plt.show()
    plt.close(fig)

# ---------------------------
# --- Main CLI / runner ---
# ---------------------------
def main():
    parser = argparse.ArgumentParser(description="FRZK: Semantic density visualizer & simple learner simulation")
    parser.add_argument("--no-sim", action="store_true", help="disable learner simulation and trajectory plot")
    parser.add_argument("--save", action="store_true", help="save generated figures to output directory")
    parser.add_argument("--out", type=str, default=OUT_DIR, help="output directory when using --save")
    args = parser.parse_args()

    hubs = DEFAULT_HUBS
    savepath_dir = args.out

    if args.save:
        os.makedirs(savepath_dir, exist_ok=True)

    # 3D scatter
    save1 = os.path.join(savepath_dir, "frzk_semantic_density_3d_scatter.png") if args.save else None
    plot_3d_scatter_density(hubs, n_axis=N_SAMP_AXIS, show=not args.save, savepath=save1)

    # XY slices
    prefix = os.path.join(savepath_dir, "frzk_semantic_density") if args.save else None
    plot_xy_slices(hubs, z_slices=Z_SLICES, res=N_SLICE_RES, out_prefix=prefix, show=not args.save)

    # Simulation of learners
    if not args.no_sim and SIMULATE_LEARNERS:
        save2 = os.path.join(savepath_dir, "frzk_learners_trajectories.png") if args.save else None
        simulate_learners_and_plot(hubs, n_learners=N_LEARNERS, n_steps=N_STEPS,
                                   alpha=ALPHA, beta=BETA, show=not args.save, savepath=save2)

    if args.save:
        print("All figures saved in:", os.path.abspath(savepath_dir))

if __name__ == "__main__":
    main()
