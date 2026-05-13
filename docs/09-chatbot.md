# 🤖 Módulo: Chatbot — Tribu Assistant

**Archivo:** `index.php` (sección chatbot al final del body)

---

## Descripción

Asistente virtual integrado en la landing page. Funciona como burbuja flotante interactiva que guía al usuario, responde preguntas frecuentes e incentiva el registro/login antes de realizar acciones que lo requieren.

---

## Estructura visual

```
#chat-fab          ← Burbuja flotante (fija, esquina inferior derecha)
  └── #chat-notif  ← Badge rojo con "1" (aparece a los 3 segundos)

#chat-window       ← Ventana expandible
  ├── Header       ← Avatar robot + nombre + indicador "escribiendo..."
  ├── #chat-messages ← Área de mensajes con scroll
  ├── #chat-quick  ← Botones de respuesta rápida contextuales
  └── Input + botón enviar
```

---

## Base de conocimiento (KB)

```javascript
var KB = {
    productos: [
        { nombre, precio, cat, emoji }  // 4 productos del menú
    ],
    horarios:   'Almuerzo: 12:00–15:00 · Cena: 18:00–22:00',
    beneficios: [...]  // 5 beneficios del registro
};
```

---

## Respuestas rápidas por contexto

| Contexto | Botones |
|---|---|
| `inicio` | Ver menú · Reservar mesa · Hacer pedido · Horarios · Ayuda |
| `menu` | Hamburguesas · Parrilla · Bebidas · Precios · Volver |
| `pedido` | Pedir ahora · Ver menú primero · Volver |
| `auth` | Iniciar sesión · Registrarme · Volver |
| `ayuda` | Ver menú · Reservar · Pedir · Contacto · Volver |

---

## Flujo de autenticación

Cuando el usuario quiere **pedir** o **reservar**:

```
Usuario: "🛒 Hacer pedido"
Bot: "Para realizar un pedido necesitas iniciar sesión."
Bot: [typing...]
Bot: "Al registrarte obtienes: [lista de beneficios]"
Bot: [typing...]
Bot: "¿Qué prefieres hacer? 👇"
Botones: [🔑 Iniciar sesión] [📝 Registrarme] [🔙 Volver]
```

Al hacer clic en "Iniciar sesión" o "Registrarme":
```javascript
window.location.href = 'views/usuarios/login.php';
// o
window.location.href = 'views/usuarios/registre.php';
```

---

## Reconocimiento de texto libre

El input libre detecta palabras clave con regex:

```javascript
if (lower.match(/hola|buenas|hey|saludos/))        → saludo
if (lower.match(/menu|menú|comida|plato/))          → respuestaMenu()
if (lower.match(/reserva|mesa|reservar/))           → respuestaReserva()
if (lower.match(/pedido|pedir|orden|comprar/))      → respuestaPedido()
if (lower.match(/horario|hora|abierto|cierra/))     → respuestaHorario()
if (lower.match(/precio|costo|cuanto|valor/))       → respuestaPrecios()
if (lower.match(/login|ingresar|iniciar|sesion/))   → redirige a login
if (lower.match(/registro|registrar|cuenta/))       → redirige a registro
if (lower.match(/hamburguesa|burger/))              → respuestaMenu('hamburguesa')
if (lower.match(/bbq|costilla|parrilla/))           → respuestaMenu('parrilla')
if (lower.match(/bebida|limonada|jugo/))            → respuestaMenu('bebida')
```

---

## Funciones principales

| Función | Descripción |
|---|---|
| `toggleChat()` | Abre/cierra la ventana. Lanza bienvenida si es la primera vez |
| `limpiarChat()` | Borra historial y relanza bienvenida |
| `addMsg(texto, tipo, delay)` | Agrega burbuja al chat con delay opcional |
| `mostrarTyping(cb, delay)` | Muestra "escribiendo..." y ejecuta callback tras delay |
| `setQuick(key)` | Renderiza botones de respuesta rápida según contexto |
| `mensajeBienvenida()` | Secuencia inicial de 2 mensajes + botones |
| `procesarQuick(label)` | Enruta el botón pulsado a la respuesta correcta |
| `enviarMensaje()` | Lee el input, lo procesa y limpia el campo |
| `respuestaMenu(filtro)` | Muestra productos filtrados por categoría |
| `respuestaReserva()` | Flujo de 3 mensajes incentivando registro |
| `respuestaPedido()` | Flujo de 3 mensajes incentivando registro |
| `respuestaHorario()` | Muestra horarios de atención |
| `respuestaPrecios()` | Lista todos los productos con precio |
| `respuestaAyuda()` | Lista temas disponibles |
| `respuestaContacto()` | Muestra información de contacto |
| `respuestaDefault()` | Respuesta aleatoria cuando no entiende |

---

## Animaciones CSS

```css
@keyframes chatIn { from{opacity:0;transform:translateY(20px) scale(.95)} to{...} }
@keyframes msgIn  { from{opacity:0;transform:translateY(8px)} to{...} }
```

- `.bubble-bot`: fondo blanco, borde redondeado izquierdo.
- `.bubble-user`: degradado naranja, borde redondeado derecho.
- `.quick-btn`: borde naranja, hover rellena de naranja.

---

## Notificación automática

```javascript
setTimeout(function() {
    if (!chatAbierto) {
        document.getElementById('chat-notif').style.display = 'flex';
    }
}, 3000); // aparece a los 3 segundos de cargar la página
```
