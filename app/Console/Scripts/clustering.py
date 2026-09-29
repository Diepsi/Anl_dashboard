"""
Regional Delivery Performance Clustering — K-Means (k=3).

Input  : CSV baris observasi  ->  kolom: kota_kabupaten,provinsi,durasi,is_out
Output : JSON  ->  {"regions": [ {kota_kabupaten, cluster_id, cluster_label, ...} ]}

Label zona ditentukan dari pusat cluster hasil scaling, bukan dari urutan indeks:
  1. zona risiko  = pusat dengan (z-aging + z-out-sla) tertinggi
  2. zona volume  = sisa dengan pusat volume tertinggi
  3. sisanya      = zona standar
"""
import json
import sys
from pathlib import Path

import numpy as np
import pandas as pd
import sklearn
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler

HIGH_RISK = "High Risk / Bottleneck Zone"
HIGH_VOLUME = "High Volume Zone"
STANDARD = "Standard / Low Risk Zone"


def main(input_csv: str, output_json: str) -> None:
    df = pd.read_csv(input_csv)
    df["durasi"] = pd.to_numeric(df["durasi"], errors="coerce")
    df["is_out"] = pd.to_numeric(df["is_out"].replace({"": pd.NA}), errors="coerce")
    df["verifiable"] = df["is_out"].notna()

    agg = (
        df.groupby("kota_kabupaten", sort=False)
        .agg(
            provinsi=("provinsi", lambda s: s.mode().iloc[0] if not s.mode().empty else ""),
            avg_aging=("durasi", "mean"),
            out=("is_out", "sum"),
            verifiable=("verifiable", "sum"),
            total_shipment=("is_out", "size"),
        )
        .reset_index()
    )

    agg["out_sla_rate"] = np.where(
        agg["verifiable"] > 0, (agg["out"] / agg["verifiable"]) * 100.0, np.nan
    )

    usable = agg.dropna(subset=["avg_aging", "out_sla_rate"]).copy()

    if len(usable) < 9:
        raise RuntimeError(
            f"wilayah ber-data lengkap hanya {len(usable)} (perlu >= 9 untuk K-Means k=3)"
        )

    features = usable[["avg_aging", "out_sla_rate", "total_shipment"]].to_numpy()
    scaler = StandardScaler()
    scaled = scaler.fit_transform(features)
    scaled_df = pd.DataFrame(
        scaled, columns=["avg_aging", "out_sla_rate", "total_shipment"], index=usable.index
    )

    kmeans = KMeans(n_clusters=3, random_state=42, n_init="auto")
    usable["cluster_id"] = kmeans.fit_predict(scaled).astype(int)

    # Labeling dari pusat cluster (dalam skala ter-standardisasi).
    centers = kmeans.cluster_centers_
    risk_score = centers[:, 0] + centers[:, 1]
    volume_score = centers[:, 2]

    risk_cluster = int(np.argmax(risk_score))
    rest = [c for c in range(3) if c != risk_cluster]
    volume_cluster = max(rest, key=lambda c: volume_score[c])
    standard_cluster = [c for c in rest if c != volume_cluster][0]

    labels = {
        risk_cluster: HIGH_RISK,
        volume_cluster: HIGH_VOLUME,
        standard_cluster: STANDARD,
    }
    for cluster_id, label in labels.items():
        usable.loc[usable["cluster_id"] == cluster_id, "cluster_label"] = label

    # Indeks risiko 0..100 dari komponen ter-standardisasi (aging + out-sla).
    usable["risk_index"] = ((scaled_df["avg_aging"] + scaled_df["out_sla_rate"]) / 2)
    usable["risk_index"] = (((usable["risk_index"].clip(-2, 2) + 2) / 4) * 100).round(1)

    usable["avg_aging"] = usable["avg_aging"].round(1)
    usable["out_sla_rate"] = usable["out_sla_rate"].round(1)

    centers = pd.DataFrame(
        scaler.inverse_transform(kmeans.cluster_centers_),
        columns=["avg_aging", "out_sla_rate", "total_shipment"],
    )

    regions = [
        {
            "kota_kabupaten": row.kota_kabupaten,
            "provinsi": row.provinsi,
            "cluster_id": int(row.cluster_id),
            "cluster_label": row.cluster_label,
            "avg_aging": float(row.avg_aging),
            "out_sla_rate": float(row.out_sla_rate),
            "total_shipment": int(row.total_shipment),
            "verifiable_count": int(row.verifiable),
            "risk_index": float(row.risk_index),
        }
        for row in usable.itertuples(index=False)
    ]

    centers_out = [
        {
            "cluster_id": int(cid),
            "cluster_label": labels[int(cid)],
            "avg_aging": round(float(row.avg_aging), 1),
            "out_sla_rate": round(float(row.out_sla_rate), 1),
            "total_shipment": round(float(row.total_shipment), 0),
        }
        for cid, row in centers.iterrows()
    ]

    Path(output_json).write_text(
        json.dumps(
            {
                "algo": "k-means-3-standard-scaler",
                "random_state": 42,
                "sklearn": sklearn.__version__,
                "n_regions": len(regions),
                "centers": centers_out,
                "regions": regions,
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )


if __name__ == "__main__":
    try:
        main(sys.argv[1], sys.argv[2])
    except Exception as exc:  # noqa: BLE001 — pesan error diteruskan ke Laravel
        print(f"clustering.py: {exc}", file=sys.stderr)
        sys.exit(1)