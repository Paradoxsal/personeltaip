// resources/js/bootstrap.js
import 'dotenv/config';
import ws from 'ws';
import Pusher from 'pusher-js';
import Echo from 'laravel-echo';

// Node.js WebSocket polyfill
global.WebSocket = ws.WebSocket;

// Pusher instance'ı oluştur
const pusherInstance = new Pusher(process.env.MIX_PUSHER_APP_KEY, {
  cluster: process.env.MIX_PUSHER_APP_CLUSTER,
  wsHost: `ws-${process.env.MIX_PUSHER_APP_CLUSTER}.pusher.com`, // Dinamik host
  wsPort: 443,
  forceTLS: true,
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
  enableLogging: true // Detaylı loglar
});

// Laravel Echo yapılandırması
const echo = new Echo({
  broadcaster: 'pusher',
  client: pusherInstance
});

// Bağlantı olayları
pusherInstance.connection.bind('connected', () => {
  console.log('✅ Pusher bağlandı! Socket ID:', pusherInstance.connection.socket_id);
});

pusherInstance.connection.bind('disconnected', () => {
  console.log('❌ Pusher bağlantısı kesildi!');
});

pusherInstance.connection.bind('error', (error) => {
  console.error('🔥 Pusher hatası:', error);
});

// Kanal işlemleri
const channelName = 'mobilpersonel-development'; // Sabit kanal adı
const eventName = '.location.updated'; // Event öneki

const channel = echo.channel(channelName);

channel.subscribed(() => {
  console.log(`📢 Kanal '${channelName}' abone olundu`);
});

channel.error((error) => {
  console.error(`🚨 Kanal '${channelName}' hatası:`, error);
});

channel.listen(eventName, (data) => {
  console.log('📍 Yeni konum verisi:', data);
});

// Bağlantıyı aktif tut
setInterval(() => {
  console.log('🔄 Bağlantı kontrolü...');
}, 5000);

console.log("Pusher bağlantısı başlatılıyor...");