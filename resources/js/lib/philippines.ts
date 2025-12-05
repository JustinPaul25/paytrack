// Philippines data for searchable selects
// Davao Region provinces only
export const PH_PROVINCES: string[] = [
  'Davao de Oro',
  'Davao del Norte',
  'Davao del Sur',
  'Davao Occidental',
  'Davao Oriental'
];

export type SelectOption = { value: string; label: string };

export const provinceOptions: SelectOption[] = PH_PROVINCES.map((p) => ({
  value: p,
  label: p
}));

// Province to Cities/Municipalities mapping (Davao Region only)
export const PROVINCE_CITIES: Record<string, string[]> = {
  'Davao de Oro': ['Compostela', 'Laak', 'Mabini', 'Maco', 'Maragusan', 'Mawab', 'Monkayo', 'Montevista', 'Nabunturan', 'New Bataan', 'Pantukan', 'Tagum'],
  'Davao del Norte': ['Asuncion', 'Braulio E. Dujali', 'Carmen', 'Kapalong', 'New Corella', 'Panabo', 'San Isidro', 'Santo Tomas', 'Tagum', 'Talaingod'],
  'Davao del Sur': ['Bansalan', 'Davao', 'Digos', 'Don Marcelino', 'Hagonoy', 'Jose Abad Santos', 'Kiblawan', 'Magsaysay', 'Malalag', 'Malita', 'Matanao', 'Padada', 'Santa Cruz', 'Santa Maria', 'Sarangani', 'Sulop'],
  'Davao Occidental': ['Don Marcelino', 'Jose Abad Santos', 'Malita', 'Santa Maria', 'Sarangani'],
  'Davao Oriental': ['Baganga', 'Banaybanay', 'Boston', 'Caraga', 'Cateel', 'Governor Generoso', 'Lupon', 'Manay', 'Mati', 'San Isidro', 'Tarragona']
};

// City/Municipality to Barangays mapping (Davao Region)
export const CITY_BARANGAYS: Record<string, string[]> = {
  // Davao de Oro
  'Compostela': ['Bagongon', 'Cabadiangan', 'Gabi', 'Lagab', 'Mangayon', 'Mapaca', 'New Alegria', 'Ngan', 'Osmeña', 'Panansalan', 'Poblacion', 'San Miguel', 'Siocon'],
  'Laak': ['Aguinaldo', 'Ampawid', 'Andap', 'Banbanon', 'Binasbas', 'Ceboleda', 'Ilpapa', 'Kaligutan', 'Kapatagan', 'Kidawa', 'Kiokmay', 'Laak', 'Langtud', 'Lapnag', 'Lapok', 'Lapuyan', 'Mabuhay', 'Magsaysay', 'Mamacao', 'Mampising', 'New Visayas', 'Poblacion', 'Sagrada', 'San Antonio', 'San Isidro', 'Santo Niño', 'Sapang', 'Sibayan', 'Sinangguyan', 'Tagugpo', 'Tigbawan', 'Tubod'],
  'Mabini': ['Anitapan', 'Cabuyuan', 'Cuambog', 'Del Pilar', 'Libas', 'Mabini', 'New Bataan', 'Pindasan', 'San Antonio', 'San Isidro', 'San Jose', 'San Vicente', 'Santa Maria', 'Santo Niño', 'Tagbongabong', 'Tibagon'],
  'Maco': ['Anibongan', 'Binuangan', 'Bucana', 'Calabcab', 'Concepcion', 'Dumlan', 'Elizalde', 'Gubatan', 'Kinuban', 'Langgawisan', 'Libay-libay', 'Limbo', 'Lumatab', 'Maco', 'Mambing', 'New Barili', 'New Leyte', 'New Opon', 'New Visayas', 'Pangibiran', 'Panibasan', 'Poblacion', 'San Antonio', 'San Isidro', 'San Juan', 'San Vicente', 'Santo Niño', 'Taglawig', 'Tulalian'],
  'Maragusan': ['Bagong Silang', 'Bantawan', 'Batangas', 'Bato', 'Bucana', 'Calapagan', 'Cawayan', 'Coronobe', 'Dapnan', 'Del Pilar', 'Lahi', 'Langgawisan', 'Lapnag', 'Mabugnao', 'Magcagong', 'Mahayahay', 'Mapawa', 'Maragusan', 'New Albay', 'New Bataan', 'New Katipunan', 'New Leyte', 'New Sibonga', 'Poblacion', 'San Isidro', 'San Mariano', 'Tagdanua', 'Tigbao', 'Tuboran'],
  'Mawab': ['Andili', 'Bato', 'Concepcion', 'Dona Alicia', 'Elizalde', 'Gumamela', 'Katipunan', 'Kauswagan', 'Lapinigan', 'Libay-libay', 'Luna', 'Mabini', 'Mawab', 'New Bataan', 'New Leyte', 'Poblacion', 'San Antonio', 'San Isidro', 'San Vicente', 'Santo Niño', 'Taglawig', 'Tuburan'],
  'Monkayo': ['Awao', 'Babag', 'Banlag', 'Baylo', 'Casoon', 'Haguimitan', 'Inambatan', 'Linoan', 'Mamunga', 'Mount Diwata', 'Naboc', 'Olaycon', 'Pasian', 'Poblacion', 'Rizal', 'Salvacion', 'San Isidro', 'San Jose', 'Santo Niño', 'Tuburan', 'Upper Ulip'],
  'Montevista': ['Banglasan', 'Bankerohan Norte', 'Bankerohan Sur', 'Camansi', 'Canidkid', 'Canipa', 'Poblacion', 'San Jose', 'San Vicente', 'Santo Niño', 'Tapia'],
  'Nabunturan': ['Anislagan', 'Antequera', 'Basak', 'Cabacungan', 'Cabidianan', 'Katipunan', 'Libasan', 'Magsaysay', 'Mainit', 'Manat', 'Matilo', 'Mipangi', 'Nabunturan', 'New Sibonga', 'Ogao', 'Pangutosan', 'Poblacion', 'San Isidro', 'San Roque', 'Santo Niño', 'Sasa', 'Tagnocon'],
  'New Bataan': ['Andap', 'Bantawan', 'Batinao', 'Cabinuangan', 'Camansi', 'Cawayan', 'Elizalde', 'Kahayag', 'Katipunan', 'Langgawisan', 'Manurigao', 'Mawab', 'New Bataan', 'Poblacion', 'San Antonio', 'San Isidro', 'San Vicente', 'Santo Niño'],
  'Pantukan': ['Bongabong', 'Bongbong', 'Burgos', 'Cabinuangan', 'Dacudao', 'Dapnan', 'Kingking', 'Las Arenas', 'Libay-libay', 'Magnaga', 'Mabini', 'Mabunga', 'Malibago', 'Napnapan', 'Poblacion', 'San Antonio', 'San Isidro', 'Santo Niño', 'Tagdangua', 'Tambongon'],
  'Tagum': ['Apokon', 'Bincungan', 'Busaon', 'Canocotan', 'Cuambogan', 'La Filipina', 'Liboganon', 'Madaum', 'Magdum', 'Magsaysay', 'New Balamban', 'New Leyte', 'Pagsabangan', 'Pandapan', 'San Agustin', 'San Isidro', 'San Miguel', 'Visayan Village'],
  
  // Davao del Norte
  'Asuncion': ['Buclad', 'Camansa', 'Canatan', 'Cawayan', 'Dona Andrea', 'Magatos', 'New Loon', 'New Santiago', 'Poblacion', 'Sagayen', 'San Vicente', 'Santa Filomena', 'Sonlon', 'Tibungco'],
  'Braulio E. Dujali': ['Cebulano', 'Dujali', 'Magupising', 'New Casay', 'Tanglaw'],
  'Carmen': ['Alejal', 'Anibongan', 'Asuncion', 'Bincungan', 'Guihing', 'Ising', 'La Paz', 'Mabaus', 'Mabuhay', 'Magupising', 'Mahayag', 'Maligaya', 'Mambing', 'New Camiling', 'New Carcar', 'Poblacion', 'Salvacion', 'San Isidro', 'Santo Niño', 'Taba', 'Tibungco', 'Tubod'],
  'Kapalong': ['Alegria', 'Ampawid', 'Anibongan', 'Aurora', 'Balagunan', 'Belis', 'Binaton', 'Capungagan', 'Florida', 'Gabuyan', 'Gupitan', 'Jose Rizal', 'Katipunan', 'Luna', 'Mabantao', 'Mamacao', 'Maniki', 'Pag-asa', 'Pagsabangan', 'Pamintaran', 'Patrocenio', 'Poblacion', 'Semong', 'Sua-on', 'Suawon', 'Tibungco', 'Tupas'],
  'New Corella': ['Cabidianan', 'Del Monte', 'Del Pilar', 'Limba-an', 'Magsaysay', 'Mambing', 'New Corella', 'Poblacion', 'San Antonio', 'San Isidro', 'San Roque', 'Santo Niño', 'Tagum'],
  'Panabo': ['A.O. Floirendo', 'Buenavista', 'Cacao', 'Cagangohan', 'Consolacion', 'Dapco', 'Gredu', 'J.P. Laurel', 'Kasilak', 'Katipunan', 'Kauswagan', 'Kiotoy', 'Little Panay', 'Lower Panaga', 'Mabunao', 'Maduao', 'Malativas', 'Mambago', 'Mankilam', 'New Malitbog', 'New Pandan', 'New Visayas', 'Poblacion', 'San Francisco', 'San Pedro', 'San Roque', 'San Vicente', 'Santo Niño', 'Tagpore', 'Tibungco', 'Upper Licanan'],
  'San Isidro': ['Dacudao', 'Datu Balong', 'Igangon', 'Kipalili', 'Libuton', 'Linao', 'Mamacao', 'Monte Dujali', 'New Casay', 'Poblacion', 'San Miguel', 'Santo Niño'],
  'Santo Tomas': ['Balagunan', 'Bobongon', 'Casig-ang', 'Esperanza', 'Kimamon', 'Kinamayan', 'La Libertad', 'Lapaz', 'Magwawa', 'New Katipunan', 'New Visayas', 'Pantaron', 'San Jose', 'San Miguel', 'Talomo', 'Tibal-og', 'Tulalian'],
  'Tagum': ['Apokon', 'Bincungan', 'Busaon', 'Canocotan', 'Cuambogan', 'La Filipina', 'Liboganon', 'Madaum', 'Magdum', 'Magsaysay', 'New Balamban', 'New Leyte', 'Pagsabangan', 'Pandapan', 'San Agustin', 'San Isidro', 'San Miguel', 'Visayan Village'],
  'Talaingod': ['Dagohoy', 'Palma Gil', 'Santo Niño'],
  
  // Davao del Sur
  'Bansalan': ['Alegre', 'Bitaug', 'Buenavista', 'Datu Salumay', 'Dumalag', 'Eman', 'Kinuskusan', 'Linan', 'Marber', 'New Clarin', 'Poblacion', 'Rizal', 'San Isidro', 'Santo Niño', 'Tubod'],
  'Davao': ['Agdao', 'Bago Aplaya', 'Bago Gallera', 'Bajada', 'Baliok', 'Bangkas Heights', 'Barangay 1-A', 'Barangay 2-A', 'Barangay 3-A', 'Barangay 4-A', 'Barangay 5-A', 'Barangay 6-A', 'Barangay 7-A', 'Barangay 8-A', 'Barangay 9-A', 'Barangay 10-A', 'Barangay 11-B', 'Barangay 12-B', 'Barangay 13-B', 'Barangay 14-B', 'Barangay 15-B', 'Barangay 16-B', 'Barangay 17-B', 'Barangay 18-B', 'Barangay 19-B', 'Barangay 20-B', 'Barangay 21-C', 'Barangay 22-C', 'Barangay 23-C', 'Barangay 24-C', 'Barangay 25-C', 'Barangay 26-C', 'Barangay 27-C', 'Barangay 28-C', 'Barangay 29-C', 'Barangay 30-C', 'Barangay 31-D', 'Barangay 32-D', 'Barangay 33-D', 'Barangay 34-D', 'Barangay 35-D', 'Barangay 36-D', 'Barangay 37-D', 'Barangay 38-D', 'Barangay 39-D', 'Barangay 40-D', 'Buhangin', 'Bunawan', 'Cabantian', 'Calinan', 'Catalunan Grande', 'Catalunan Pequeño', 'Crocodile Park', 'Dacudao', 'Daliao', 'Dumoy', 'Ecoland', 'Indangan', 'Lacson', 'Langub', 'Lasang', 'Ma-a', 'Magtuod', 'Mampangi', 'Mandug', 'Matina Aplaya', 'Matina Crossing', 'Matina Pangi', 'Mintal', 'New Valencia', 'Pampanga', 'Pandaitan', 'Poblacion', 'Riverside', 'Sasa', 'Talomo', 'Tibungco', 'Tigatto', 'Toril', 'Ula', 'Waan'],
  'Digos': ['Aplaya', 'Balabag', 'Binaton', 'Cogon', 'Colon', 'Dawis', 'Dulangan', 'Goma', 'Igpit', 'Kapatagan', 'Kiagot', 'Lungag', 'Mahayahay', 'Matti', 'New Opon', 'Poblacion', 'Rizal', 'San Agustin', 'San Isidro', 'San Jose', 'San Miguel', 'San Roque', 'Santo Niño', 'Tacul', 'Tiguman'],
  'Don Marcelino': ['Baluntayan', 'Calian', 'Lapuan', 'North Lamidan', 'Poblacion', 'South Lamidan', 'Talagutong'],
  'Hagonoy': ['Aplaya', 'Balutakay', 'Clib', 'Guihing', 'Hagonoy Crossing', 'Kibuaya', 'La Union', 'Lanang', 'Lapulabao', 'Leling', 'Mahayahay', 'Malabang', 'New Quezon', 'Poblacion', 'San Isidro', 'San Miguel', 'Sinayawan', 'Tuban'],
  'Jose Abad Santos': ['Balangonan', 'Bato', 'Culaman', 'Malita', 'Poblacion', 'Sugal'],
  'Kiblawan': ['Abnate', 'Bagong Silang', 'Bunot', 'Cogon', 'Dapok', 'Ihan', 'Kiblawan', 'Kimlawis', 'Kisulan', 'Lamanan', 'Lapulabao', 'Managa', 'Maraga-a', 'Molopolo', 'New Sibonga', 'Poblacion', 'San Isidro', 'Santo Niño', 'Tacul', 'Tubod'],
  'Magsaysay': ['Bacungan', 'Baluntayan', 'Bato', 'Binuangan', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Magsaysay', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Malalag': ['Bagong Silang', 'Bato', 'Baybay', 'Binuangan', 'Bolton', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malalag', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Malita': ['Bacungan', 'Baluntayan', 'Bato', 'Binuangan', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malita', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Matanao': ['Asbang', 'Bato', 'Colonsabak', 'Dumlan', 'Kapatagan', 'Kibawisan', 'Lapnag', 'Mabini', 'Matanao', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Padada': ['Alambre', 'Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Padada', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Santa Cruz': ['Astorga', 'Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santa Cruz', 'Santo Niño', 'Taglawig'],
  'Santa Maria': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santa Maria', 'Santo Niño', 'Taglawig'],
  'Sarangani': ['Baluntayan', 'Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Sarangani', 'Santo Niño', 'Taglawig'],
  'Sulop': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Sulop', 'Taglawig'],
  
  // Davao Occidental
  'Don Marcelino': ['Baluntayan', 'Calian', 'Lapuan', 'North Lamidan', 'Poblacion', 'South Lamidan', 'Talagutong'],
  'Jose Abad Santos': ['Balangonan', 'Bato', 'Culaman', 'Malita', 'Poblacion', 'Sugal'],
  'Malita': ['Bacungan', 'Baluntayan', 'Bato', 'Binuangan', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Santa Maria': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santa Maria', 'Santo Niño', 'Taglawig'],
  'Sarangani': ['Baluntayan', 'Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Sarangani', 'Santo Niño', 'Taglawig'],
  
  // Davao Oriental
  'Baganga': ['Baculin', 'Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Banaybanay': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Boston': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Caraga': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Cateel': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Governor Generoso': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Lupon': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Manay': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Mati': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'San Isidro': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig'],
  'Tarragona': ['Bato', 'Buclad', 'Cawayan', 'Dapnan', 'Dumlan', 'Gupitan', 'Kapatagan', 'Lapnag', 'Mabini', 'Malibago', 'New Bataan', 'Poblacion', 'San Isidro', 'Santo Niño', 'Taglawig']
};

// Helper function to get cities/municipalities for a province
export function getCitiesForProvince(province: string | null | undefined): SelectOption[] {
  if (!province) return [];
  const cities = PROVINCE_CITIES[province] || [];
  return cities.map(city => ({ value: city, label: city }));
}

// Helper function to get barangays for a city/municipality
export function getBarangaysForCity(city: string | null | undefined): SelectOption[] {
  if (!city) return [];
  const barangays = CITY_BARANGAYS[city] || [];
  return barangays.map(barangay => ({ value: barangay, label: barangay }));
}

