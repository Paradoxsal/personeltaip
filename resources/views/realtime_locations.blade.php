<!DOCTYPE html>
<html>
<head>
    <title>Anlık Konum Takibi</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body>
    <h1>Son Konum Bilgisi</h1>
    <div id="locationData">
        <p>Kullanıcı ID: <span id="userId">-</span></p>
        <p>Enlem: <span id="latitude">-</span></p>
        <p>Boylam: <span id="longitude">-</span></p>
    </div>

    <script>
        // Pusher bağlantısı
        const pusher = new Pusher('119fb54c8cac893dae6c', {
            cluster: 'eu',
            forceTLS: true,
            enabledTransports: ['ws', 'wss']
        });

        pusher.connection.bind('connected', () => {
            console.log('✅ Pusher bağlandı! Socket ID:', pusher.connection.socket_id);
        });

        pusher.connection.bind('error', (error) => {
            console.error('🔥 Pusher hatası:', error);
        });

        // Kanalı dinle
        const channel = pusher.subscribe('mobilpersonel-development');
        console.log('Kanal adı:', channel.name);

        // Event dinleyici (nokta öneki ile)
        channel.bind('.location.updated', (data) => {
            console.log('📍 Veri alındı:', data);
            
            if (data && data.userId && data.latitude && data.longitude) {
                document.getElementById('userId').textContent = data.userId;
                document.getElementById('latitude').textContent = data.latitude;
                document.getElementById('longitude').textContent = data.longitude;
            } else {
                console.error('Hatalı veri formatı:', data);
            }
        });

        console.log('Web sayfası hazır! Pusher dinleniyor...');
    </script>
</body>
</html>
