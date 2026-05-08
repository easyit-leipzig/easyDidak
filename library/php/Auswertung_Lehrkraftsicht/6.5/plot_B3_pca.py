import json
import numpy as np
import matplotlib.pyplot as plt
from sklearn.decomposition import PCA

with open("lehrkraft1.json", encoding="utf-8") as f:
    data_l1 = json.load(f)["daten"]
with open("nicht_l1.json", encoding="utf-8") as f:
    data_nl1 = json.load(f)["daten"]

def to_matrix(data):
    return np.array([
        [
            d["vektor_normiert"]["kognition"],
            d["vektor_normiert"]["sozial"],
            d["vektor_normiert"]["affektiv"],
            d["vektor_normiert"]["motivation"],
            d["vektor_normiert"]["methodik"],
            d["vektor_normiert"]["performanz"],
            d["vektor_normiert"]["regulation"],
        ]
        for d in data
    ])

X1 = to_matrix(data_l1)
X2 = to_matrix(data_nl1)

pca = PCA(n_components=2)
X_all = np.vstack([X1, X2])
X_pca = pca.fit_transform(X_all)

n1 = len(X1)

plt.figure()
plt.scatter(X_pca[:n1,0], X_pca[:n1,1], label="L1", alpha=0.5)
plt.scatter(X_pca[n1:,0], X_pca[n1:,1], label="≠L1", alpha=0.5)
plt.legend()
plt.title("Strukturraum (PCA)")
plt.show()
