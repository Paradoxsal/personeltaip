<!DOCTYPE html>
<html>
<head>
    <title>Anlık Konum Takibi</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <style>
      body { font-family: Arial, sans-serif; }
      #locationList { list-style: none; padding: 0; }
      #locationList li { padding: 8px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Son Konum Bilgileri</h1>
    <div id="locationData">
        <ul id="locationList">
            <!-- Yeni konum verileri buraya eklenecek -->
        </ul>
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

        // Event dinleyici: gelen veriyi listeye ekleyelim
        channel.bind('location.updated', (data) => {
            console.log('📍 Veri alındı:', data);

            if (data && data.userId && data.latitude && data.longitude) {
                const list = document.getElementById('locationList');
                const li = document.createElement('li');
                li.textContent = `Kullanıcı ID: ${data.userId} | Enlem: ${data.latitude} | Boylam: ${data.longitude} | Zaman: ${new Date().toLocaleTimeString()}`;
                // Yeni veriyi listenin başına ekle (son gelen en üstte)
                list.prepend(li);
            } else {
                console.error('Hatalı veri formatı:', data);
            }
        });

        console.log('Web sayfası hazır! Pusher dinleniyor...');
    </script>
</body>
</html>
