# Plantillas legales base para el MVP (Perú)

- **Estado:** borradores para revisión legal. **No son textos definitivos.**
- **Fecha:** 2026-11-16
- **Uso:** completar campos entre corchetes y validar con abogado/contador antes de publicar.
- **Base:** `specs/007-mvp-sorteos/especificacion.md`.

> **Aviso:** estas plantillas son una estructura de trabajo. No constituyen asesoría legal. La calificación del sorteo, la autorización, los tributos, la protección de datos y las obligaciones AML deben validarse con profesionales en Perú.

---

## 1. Instrucciones de uso

1. Reemplazar todos los campos `[ENTRE CORCHETES]`.
2. Confirmar si el sorteo es promocional o venta de participaciones.
3. Confirmar autorización/resolución si corresponde.
4. Revisar bases, privacidad y Libro de Reclamaciones con abogado.
5. Publicar la versión aprobada y registrar su hash/fecha.
6. No modificar una versión publicada; crear una nueva versión.

---

## 2. Bases y condiciones del sorteo

### 2.1 Organizador

- Razón social / nombre: `[ORGANIZADOR]`.
- RUC / documento: `[RUC]`.
- Domicilio: `[DOMICILIO]`.
- Correo/contacto: `[CONTACTO]`.
- Teléfono/WhatsApp: `[TELÉFONO]`.

### 2.2 Objeto

El organizador realiza el sorteo denominado **`[NOMBRE DEL SORTEO]`**, cuyo premio es **`[DESCRIPCIÓN DEL PREMIO]`**, con el propósito de `[FINALIDAD]`.

### 2.3 Ámbito y vigencia

- Ámbito territorial: `[PERÚ / LIMA / OTRO]`.
- Fecha de inicio de ventas: `[FECHA]`.
- Fecha de cierre de ventas: `[FECHA]`.
- Fecha del sorteo: `[FECHA]`.
- Fecha límite de reclamo: `[FECHA]`.

### 2.4 Elegibilidad

- Podrán participar personas naturales mayores de 18 años con DNI válido.
- No podrán participar `[ORGANIZADORES, EMPLEADOS, FAMILIARES, PROVEEDORES U OTROS EXCLUIDOS]`.
- La participación implica aceptar estas bases y la política de privacidad.
- La edad se declara al participar y se verifica al reclamar el premio.

### 2.5 Premio

- Premio: `[PRODUCTO/SERVICIO]`.
- Descripción: `[DESCRIPCIÓN]`.
- Valor referencial: `S/ [VALOR]`.
- Condiciones: `[CONDICIONES]`.
- Entrega: `[PRESENCIAL / DELIVERY / TRANSFERENCIA / OTRO]`.
- El premio no es transferible ni canjeable por dinero, salvo decisión del organizador.
- La imagen del premio es referencial; puede variar en detalles no esenciales.

### 2.6 Precio, cupos y límite

- Precio por boleto: `S/ [PRECIO]`.
- Cupos: `[NÚMERO]`.
- Máximo de boletos por persona: `[NÚMERO]`.
- Moneda: Soles (PEN).
- El monto debe pagarse completo; no se aceptan pagos parciales.

### 2.7 Mecánica de participación

1. El participante ingresa a `[URL]`.
2. Completa nombre, DNI, celular y correo.
3. Acepta las bases y la política de privacidad.
4. Elige la cantidad de boletos.
5. El sistema reserva números por 30 minutos.
6. El participante paga por Yape, Plin o transferencia BCP.
7. Sube el comprobante.
8. El organizador valida el pago.
9. Solo con pago aprobado se emiten los boletos.

### 2.8 Números y boletos

- Los números se asignan automáticamente.
- Cada número es único por sorteo.
- Los boletos no son transferibles, salvo autorización expresa.
- La reserva no equivale a compra hasta que el pago sea aprobado.
- Si la reserva expira, los números se liberan.

### 2.9 Pagos

- Métodos: Yape, Plin y transferencia BCP.
- El participante debe pagar el monto exacto.
- El comprobante es evidencia; no confirma el pago por sí solo.
- Si paga de más, puede contactar al número visible en la página para regularizar.
- Si paga de menos o el comprobante no coincide, el pago puede ser rechazado.
- Los pagos aprobados no se editan; se reembolsan o anulan con evidencia.
- Política de reembolso: `[POLÍTICA]`.

### 2.10 Sorteo

- El sorteo se realizará el `[FECHA]` a las `[HORA]`.
- Se usará un mecanismo verificable commit-reveal:
  - Se congela la lista de boletos pagados.
  - Se publica el hash de la lista.
  - Se publica el hash de la semilla antes del sorteo.
  - Se revela la semilla después.
  - El ganador se calcula de forma determinista.
- El resultado se publicará en `[URL]`.
- Se publicarán ganador, suplentes, semilla, hashes y acta.
- El organizador no modificará manualmente el resultado.

### 2.11 Resultados y transparencia

- Los resultados se publicarán en `[URL]`.
- Se publicará una lista anonimizada de participaciones.
- No se publicarán DNI, correo, celular, dirección ni comprobantes.
- El acta estará disponible para descarga.
- El nombre del ganador se publicará solo si las bases lo permiten y existe consentimiento cuando corresponda.

### 2.12 Entrega del premio

- El organizador contactará al ganador por `[CANAL]`.
- El ganador deberá acreditar identidad con DNI.
- Plazo de reclamo: `[PLAZO]`.
- Lugar/modalidad de entrega: `[DETALLE]`.
- Se registrará evidencia de la entrega.
- Si el ganador no reclama en plazo, se aplicará `[SUPLENTE / PREMIO DESIERTO / OTRA REGLA]`.
- Los gastos de entrega `[INCLUIDOS / NO INCLUIDOS]`.

### 2.13 Datos personales

- Responsable: `[ORGANIZADOR]`.
- Finalidad: gestión del sorteo, pago, contacto, entrega y cumplimiento legal.
- Datos: nombre, DNI, celular, correo, comprobante y evidencia de entrega.
- La política de privacidad forma parte de estas bases.
- El participante puede ejercer sus derechos ARCO en `[CONTACTO]`.

### 2.14 Reclamos

- Libro de Reclamaciones virtual: `[URL]`.
- Contacto: `[TELÉFONO/CORREO]`.
- Plazo de atención: conforme a la normativa aplicable.
- El reclamo no suspende el sorteo, salvo decisión del organizador o autoridad.

### 2.15 Autorización

- Autorización/resolución: `[NÚMERO / NO APLICA]`.
- Autoridad: `[AUTORIDAD]`.
- La autorización se mostrará en la página del sorteo cuando corresponda.

### 2.16 Modificaciones y cancelación

- El organizador podrá cancelar o reprogramar el sorteo por causa justificada, comunicando en `[URL]`.
- Si se cancela, se definirán devoluciones conforme a `[POLÍTICA]`.
- Las bases publicadas no se modifican; se crea una nueva versión.

### 2.17 Aceptación

La participación implica la aceptación plena de estas bases, de la política de privacidad y de las decisiones del organizador conforme a la ley.

---

## 3. Política de privacidad (borrador)

### 3.1 Responsable

- `[ORGANIZADOR]`, RUC `[RUC]`, domicilio `[DOMICILIO]`, contacto `[CONTACTO]`.

### 3.2 Datos que recopilamos

- Nombre completo.
- DNI.
- Celular.
- Correo electrónico.
- Comprobante de pago.
- Evidencia de entrega, si corresponde.
- Datos técnicos de seguridad (IP, user agent, fecha).

### 3.3 Finalidad

- Gestionar la participación y el pago.
- Validar identidad y evitar duplicados.
- Contactar al ganador y entregar el premio.
- Atender reclamos y cumplir obligaciones legales.
- Enviar comunicaciones transaccionales; marketing solo con consentimiento.

### 3.4 Base legal

- Consentimiento del titular.
- Ejecución de la relación de participación.
- Cumplimiento de obligaciones legales.
- Interés legítimo en seguridad y prevención de fraude, cuando corresponda.

### 3.5 Conservación

- Los datos se conservarán por `[PLAZO]` o el tiempo exigido por la ley.
- Luego se eliminarán o anonimizarán.

### 3.6 Destinatarios

- Proveedores de pago, soporte, hosting y logística, con obligaciones de confidencialidad.
- Autoridades, cuando la ley lo exija.

### 3.7 Seguridad

- Acceso restringido.
- Cifrado/hash de datos sensibles.
- Registro de auditoría.
- Backups y controles de acceso.

### 3.8 Derechos ARCO

El titular puede ejercer acceso, rectificación, cancelación y oposición en `[CONTACTO]`, adjuntando identificación.

### 3.9 Cambios

La política puede actualizarse; se publicará la versión vigente con fecha.

---

## 4. Términos de uso (borrador)

- El sitio es informativo y transaccional para el sorteo.
- El usuario se compromete a proporcionar datos veraces.
- No se permite el uso fraudulento, automatizado o abusivo.
- El organizador puede suspender cuentas/participaciones ante fraude.
- Los contenidos, marcas e imágenes pertenecen a sus titulares.
- El uso del sitio implica aceptar estos términos y la privacidad.
- Contacto: `[CONTACTO]`.

---

## 5. Libro de Reclamaciones virtual (borrador)

### 5.1 Aviso

El proveedor cuenta con Libro de Reclamaciones virtual conforme a la normativa de protección al consumidor. También mantiene un libro físico de respaldo cuando corresponda.

### 5.2 Formulario

- Nombre del consumidor.
- DNI.
- Domicilio.
- Teléfono/correo.
- Tipo: queja o reclamo.
- Detalle del bien/servicio.
- Pedido del consumidor.
- Fecha y hora.
- Constancia de recepción.

### 5.3 Plazo

El proveedor atenderá el reclamo en el plazo legal aplicable y entregará constancia. El consumidor puede acudir a INDECOPI si no recibe respuesta.

### 5.4 Contacto

- `[ORGANIZADOR]`.
- `[TELÉFONO/CORREO]`.
- `[URL]`.

---

## 6. Consentimiento de imagen y nombre (borrador)

Autorizo a `[ORGANIZADOR]` a usar mi nombre e imagen en la publicación de resultados y comunicación del sorteo, en `[MEDIOS]`, con la finalidad de transparencia y difusión. Entiendo que puedo revocar este consentimiento por escrito a `[CONTACTO]`, sin afectar la validez del sorteo.

Nombre: `[NOMBRE]`.
DNI: `[DNI]`.
Fecha: `[FECHA]`.
Firma/aceptación digital: `[ ]`.

---

## 7. Checklist legal de publicación

- [ ] Calificación del sorteo validada.
- [ ] Autorización/resolución obtenida o “no aplica” confirmado.
- [ ] Bases aprobadas y versionadas.
- [ ] Política de privacidad aprobada.
- [ ] Términos aprobados.
- [ ] Libro de Reclamaciones disponible.
- [ ] Edad mínima y restricciones definidas.
- [ ] Política de reembolso y no reclamado definida.
- [ ] Datos del organizador y contacto visibles.
- [ ] Tratamiento de datos y ARCO definido.
- [ ] Evaluación de inscripción de banco de datos/ODP.
- [ ] Evaluación tributaria del cobro.
- [ ] Evaluación AML/UIF si corresponde.
- [ ] Consentimiento de imagen/nombre definido.
- [ ] Versión publicada y hash registrados.

---

## 8. Referencias de validación

- Ley N.° 29733, Ley de Protección de Datos Personales y su Reglamento (D.S. N.° 016-2024-JUS).
- INDECOPI, Libro de Reclamaciones: https://consumidor.gob.pe/libro-de-reclamaciones/
- INDECOPI, protección al consumidor y publicidad.
- SUNAT, comprobantes electrónicos y obligaciones tributarias: https://www.sunat.gob.pe/
- SBS, prevención de lavado de activos y sujetos obligados: https://www.sbs.gob.pe/prevencion-de-lavado-activos/Sujetos-Obligados/Relacion-de-Sujetos-Obligados
- Autoridad competente para autorización de sorteos: validar según el tipo de sorteo.

> Verificar la vigencia y aplicabilidad con un profesional antes de publicar.

---

## 9. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Plantillas base para revisión legal del MVP. |
