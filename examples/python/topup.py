"""Supercell top-up with the Topupz API. pip install requests
Usage: TOPUPZ_API_KEY=tpz_... python topup.py player@gmail.com
"""
import os
import sys
import requests

BASE = "https://api.topupz.shop/api/v1/reseller"
HEADERS = {
    "Accept": "application/json",
    "X-API-Key": os.environ["TOPUPZ_API_KEY"],
    "X-REGION-KEY": "default",
}


def api(method, path, body=None):
    r = requests.request(method, BASE + path, headers=HEADERS, json=body, timeout=30)
    data = r.json()
    if not data.get("success"):
        raise RuntimeError(f"{r.status_code} {data.get('message')}")
    return data["data"]


email = sys.argv[1]
print("Balance:", api("GET", "/balance"))

# 54 = Brawl Stars, 55 = Clash of Clans, 56 = Clash Royale, 57 = Hay Day
products = api("GET", "/products?category_id=55")
gems = next((p for p in products if "80 Gems" in p["name"]), products[0])
print("Buying:", gems["name"], gems["price"], gems["currency"])

order = api("POST", "/orders", {"product_id": gems["id"], "fields": {"supercell_id_email": email}})
print("Order", order["id"], order["status"], order["fulfillment"])
# When order["fulfillment"]["requires_otp"] is True:
# api("POST", f"/orders/{order['id']}/otp", {"otp": "123456"})
