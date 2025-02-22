import 'dotenv/config'; // .env dosyasını yükle
import ws from 'ws';
import Pusher from 'pusher-js';
import Echo from 'laravel-echo';

// WebSocket Polyfill
global.WebSocket = ws.WebSocket;

// Cluster kontrolü yap
if (!process.env.MIX_PUSHER_APP_CLUSTER) {
  throw new Error("MIX_PUSHER_APP_CLUSTER .env'de tanımlı değil!");
}

// Pusher instance'ı oluştur
const pusherInstance = new Pusher(process.env.MIX_PUSHER_APP_KEY, {
  cluster: process.env.MIX_PUSHER_APP_CLUSTER, // <--- BU SATIR ÇOK ÖNEMLİ
  forceTLS: true,
  wsHost: 'ws-' + process.env.MIX_PUSHER_APP_CLUSTER + '.pusher.com',
  wsPort: 443
});

// Echo'yu başlat
const echo = new Echo({
  broadcaster: 'pusher',
  client: pusherInstance
});

console.log("Pusher bağlanıyor...");

// Kanalı dinle
echo.channel("location-updates")
   .listen(".location.updated", (data) => {
     console.log("Veri alındı:", data);
   })
   .error((error) => {
     console.error("Hata:", error);
   });