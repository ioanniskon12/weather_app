function importAll(r) {
  return r.keys().map((item) => r(item));
 }

export default function randomBGImg() {
  let imgs = importAll(require.context('../Assets', false, /\.(png|jpe?g|svg)$/));
  return imgs[Math.floor(Math.random() * imgs.length)];
}