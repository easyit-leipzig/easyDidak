import json
import pandas as pd
import matplotlib.pyplot as plt

def plot_block(df, title, output_prefix):
    df = df.sort_values("kw")

    plt.figure()
    for col in ["kognition","sozial","affektiv","motivation","methodik","performanz","regulation"]:
        plt.plot(df["kw"], df[col], label=col)

    plt.title(title + " – Dimensionen")
    plt.legend()
    plt.savefig(output_prefix + "_dimensionen.png")
    plt.close()

    plt.figure()
    plt.plot(df["kw"], df["kohärenzindex"], label="kohärenz")
    plt.plot(df["kw"], df["stabilitaetsindex"], label="stabilität")
    plt.plot(df["kw"], df["polarisierungsindex"], label="polarisierung")
    plt.legend()
    plt.title(title + " – Indizes")
    plt.savefig(output_prefix + "_indizes.png")
    plt.close()


def main():
    with open("frzk_output.json", encoding="utf-8") as f:
        data = json.load(f)

    for key in ["alle", "lehrkraft_1", "nicht_lehrkraft_1"]:
        df = pd.DataFrame(data[key]["summary_table"])
        df.to_csv(f"{key}.csv", index=False)
        plot_block(df, key, key)


if __name__ == "__main__":
    main()