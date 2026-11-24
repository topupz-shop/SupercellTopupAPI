// Supercell top-up with the Topupz API (Node 18+, no dependencies).
// Usage: TOPUPZ_API_KEY=tpz_... node topup.mjs player@gmail.com
const BASE = "https://api.topupz.shop/api/v1/reseller";
const KEY = process.env.TOPUPZ_API_KEY;

async function api(method, path, body) {
  const res = await fetch(BASE + path, {
    method,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-API-Key": KEY,
      "X-REGION-KEY": "default",
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  const json = await res.json();
  if (!json.success) throw new Error(`${res.status} ${json.message}`);
  return json.data;
}

const email = process.argv[2];
const balance = await api("GET", "/balance");
console.log("Balance:", balance.balance, balance.currency);

// 54 = Brawl Stars, 55 = Clash of Clans, 56 = Clash Royale, 57 = Hay Day
const products = await api("GET", "/products?category_id=54");
const gems = products.find((p) => p.name.includes("30 Gems")) ?? products[0];
console.log("Buying:", gems.name, gems.price, gems.currency);

const order = await api("POST", "/orders", {
  product_id: gems.id,
  fields: { supercell_id_email: email },
});
console.log("Order", order.id, order.status, order.fulfillment);
// When order.fulfillment.requires_otp becomes true:
// await api("POST", `/orders/${order.id}/otp`, { otp: "123456" });

// Saved accounts: every completed order saves the game account it used.
// Filter by email, order_id or game; sort by date or order_id, asc or desc.
const saved = await api("GET", `/accounts?game=54&email=${encodeURIComponent(email)}&sort=order_id&order=desc`);
console.log("Saved accounts:", saved.items.map((a) => `${a.id} ${a.title} (last order ${a.last_order_id})`));
if (saved.items[0]) {
  // Order again without sending the fields: they come from the saved account.
  const again = await api("POST", "/orders", { product_id: gems.id, account_id: saved.items[0].id });
  console.log("Repeat order", again.id, again.status);
}
