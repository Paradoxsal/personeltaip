// Mapbox Token (kendi tokeninizi buraya koyun)
mapboxgl.accessToken = 'pk.eyJ1IjoiYWxrYW43NyIsImEiOiJjbTNpc3lnMTYwM3hkMmtzZWtkc25veDczIn0.LswODNY4PsYagu5Hk6wM4g';

// Haritayı oluştur
const map = new mapboxgl.Map({
    container: 'map', // Haritanın render edileceği HTML elementinin ID'si
    style: 'mapbox://styles/mapbox/streets-v11', // Harita stili
    center: [28.9784, 41.0082], // Başlangıç koordinatları (örn: İstanbul)
    zoom: 12 // Başlangıç zoom seviyesi
});

// Marker (işaretçi) ekle ve taşınabilir yap
const marker = new mapboxgl.Marker({
    draggable: true
})
    .setLngLat([28.9784, 41.0082]) // Başlangıç koordinatları
    .addTo(map);

// İşaretçi sürüklendiğinde input'a koordinatları yaz
marker.on('dragend', () => {
    const lngLat = marker.getLngLat();
    document.getElementById('location').value = `${lngLat.lat}, ${lngLat.lng}`;
});

// Haritaya tıklanıldığında marker'ın yerini değiştir ve input'u güncelle
map.on('click', (event) => {
    const coordinates = event.lngLat;
    marker.setLngLat([coordinates.lng, coordinates.lat]);
    document.getElementById('location').value = `${coordinates.lat}, ${coordinates.lng}`;
});
