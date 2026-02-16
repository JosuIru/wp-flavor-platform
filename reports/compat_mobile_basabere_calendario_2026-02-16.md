# Compatibilidad móvil: Basabere Campamentos + Calendario Experiencias
Fecha: 2026-02-16 13:45:52

- Resultado global: **17/19 PASS**
- Base URL: `http://localhost:10028`

## Casos
- PASS `DISCOVERY_INFO_200` (status=200): {"wordpress_url":"http:\/\/localhost:10028","site_name":"sitio prueba","site_description":"","app_name":"sitio prueba","app_description":"","active_systems":[{"id":"calendario-experiencias","name":"Calendario de Experien
- PASS `DISCOVERY_HAS_BASABERE_CAMPS` (status=200): ['calendario-experiencias', 'basabere-campamentos', 'flavor-chat-ia']
- PASS `DISCOVERY_HAS_CALENDARIO_EXP` (status=200): ['calendario-experiencias', 'basabere-campamentos', 'flavor-chat-ia']
- PASS `DISCOVERY_MODULES_200` (status=200): {"success":true,"modules":[{"id":"woocommerce","name":"WooCommerce","description":"Integraci\u00f3n con WooCommerce: carrito, productos, pedidos y m\u00e1s.","system":"flavor-chat-ia","api_namespace":"flavor-chat-ia\/v1"
- PASS `DISCOVERY_THEME_200` (status=200): {"primary_color":"#104772","secondary_color":"#8BC34A","accent_color":"#FF9800","background_color":"#FFFFFF","surface_color":"#FFFFFF","text_primary_color":"#212121","text_secondary_color":"#757575","logo_url":"http:\/\/
- PASS `CAMPS_ENDPOINT_CAMPS` (status=200): {"success":true,"camps":[],"total":0}
- PASS `CAMPS_ENDPOINT_CAMPS` (status=401): {"code":"rest_unauthorized","message":"Token de autenticaci\u00f3n inv\u00e1lido o expirado","data":{"status":401}}
- PASS `CAMPS_ENDPOINT_STATS` (status=401): {"code":"rest_unauthorized","message":"Token de autenticaci\u00f3n inv\u00e1lido o expirado","data":{"status":401}}
- FAIL `CAMPS_DETAIL_200` (status=): no camp ids discovered from camps list
- PASS `MOBILE_PUBLIC_AVAILABILITY_200` (status=200): {"success":true,"from":"2026-02-16","to":"2026-03-18","availability":[]}
- PASS `MOBILE_PUBLIC_EXPERIENCES_200` (status=200): {"success":true,"experiences":[{"id":"abierto","name":"abierto","description":"","color":"#00a99d","duration":"","schedules":[]},{"id":"abierto-solo-por-las-mananas","name":"abierto solo por las ma\u00f1anas","description":"","color":"#aef4
- PASS `MOBILE_PUBLIC_TICKETS_200` (status=200): {"success":true,"state":null,"tickets":[{"slug":"ticket_1750070923515","name":"Ni\u00f1o LIbre, de 3 a 13 a\u00f1os","description":"<p>Visita la granja por tu cuenta, tambi\u00e9n podr\u00e1s disfrutar de la charla educativa de aves rapaces
- FAIL `AVAILABILITY_HAS_DAY_STATES` (status=200): days(list)=0
- PASS `RES_CHECK_RESPONDS` (status=200): {"success":true,"available":false,"reason":"D\u00eda no disponible"}
- PASS `RES_PREPARE_RESPONDS` (status=200): {"success":true,"reservation":{"date":"2026-02-23","items":[{"slug":"ticket_1750070923515","name":"Ni\u00f1o LIbre, de 3 a 13 a\u00f1os","quantity":1,"price":10,"subtotal":10}],"total":10,"customer":null}}
- PASS `RES_ADD_TO_CART_RESPONDS` (status=200): {"success":true,"cart_url":"http:\/\/localhost:10028","checkout_url":"http:\/\/localhost:10028","message":"A\u00f1adidos 1 productos al carrito","cart_count":1,"cart_total":"<span class=\"woocommerce-Price-amount amount\"><bdi><span class=\"woocommerce-Price-c
- PASS `RES_MOBILE_CHECKOUT_URL_RESPONDS` (status=200): {"success":true,"checkout_url":"http:\/\/localhost:10028\/?mobile_cart=eyJkYXRlIjoiMjAyNi0wMi0yMyIsInRpY2tldHMiOlt7InNsdWciOiJ0aWNrZXRfMTc1MDA3MDkyMzUxNSIsInF1YW50aXR5IjoxfV0sInRpbWVzdGFtcCI6MTc3MTI0NTk1Mn0&sig=4d006b00d4051872","message":"Valid token"}
- PASS `RES_MOBILE_CHECKOUT_URL_VALID` (status=200): {"success":true,"checkout_url":"http:\/\/localhost:10028\/?mobile_cart=eyJkYXRlIjoiMjAyNi0wMi0yMyIsInRpY2tldHMiOlt7InNsdWciOiJ0aWNrZXRfMTc1MDA3MDkyMzUxNSIsInF1YW50aXR5IjoxfV0sInRpbWVzdGFtcCI6MTc3MTI0NTk1Mn0&sig=4d006b00d4051872","message":"Valid token"}
- PASS `ADMIN_RESERVATIONS_WITH_TOKEN` (status=200): {"success":true,"reservations":[],"total":0,"limit":50,"offset":0}
