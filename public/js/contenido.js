(() => {
    const keys = {
        usuarios: "regenera_usuarios",
        voluntariados: "regenera_voluntariados",
        inscripciones: "regenera_inscripciones",
        asistencias: "regenera_asistencias",
        articulos: "regenera_articulos",
        notificaciones: "regenera_notificaciones",
        auditoria: "regenera_auditoria",
        lugares: "regenera_lugares",
        floraFauna: "regenera_flora_fauna",
        medallas: "regenera_medallas",
        usuarioMedallas: "regenera_usuario_medallas",
        puntos: "regenera_puntos",
        codigosAsistencia: "regenera_codigos_asistencia",
        sesion: "regenera_sesion"
    };
    const categories = [
        "limpieza",
        "restauración",
        "reforestación",
        "monitoreo",
        "conservación",
        "educación ambiental"
    ];
    let generatedId = 0;
    const demoMedals = [
        { medalla_id: "medalla-primer-paso", nombre: "Primer paso", icono: "🌱", descripcion: "Completaste tu primera actividad ambiental.", puntos: 20, criterio: "Completar 1 voluntariado." },
        { medalla_id: "medalla-guardian-costa", nombre: "Guardián de la costa", icono: "🌊", descripcion: "Has participado activamente en el cuidado de nuestros ecosistemas costeros.", puntos: 30, criterio: "Completar 3 actividades de limpieza o conservación." },
        { medalla_id: "medalla-guardian-territorio", nombre: "Guardián del territorio", icono: "🌵", descripcion: "Tu participación ya ha llegado a distintos rincones de BCS.", puntos: 30, criterio: "Participar en actividades en 3 lugares diferentes." },
        { medalla_id: "medalla-voluntario-constante", nombre: "Voluntario constante", icono: "🤝", descripcion: "La constancia de tu participación fortalece a la comunidad.", puntos: 40, criterio: "Completar 5 voluntariados." },
        { medalla_id: "medalla-impacto-local", nombre: "Impacto local", icono: "🌎", descripcion: "Tu compromiso contribuye a un impacto ambiental sostenido.", puntos: 60, criterio: "Completar 10 voluntariados." },
        { medalla_id: "medalla-voz-ambiental", nombre: "Voz ambiental", icono: "📝", descripcion: "Compartiste conocimiento ambiental con la comunidad.", puntos: 20, criterio: "Publicar 1 artículo aprobado." },
        { medalla_id: "medalla-comunicador", nombre: "Comunicador ambiental", icono: "📚", descripcion: "Has contribuido varias veces a la conversación ambiental.", puntos: 35, criterio: "Publicar 3 artículos aprobados." },
        { medalla_id: "medalla-educador", nombre: "Educador ambiental", icono: "🌿", descripcion: "Tu voz ayuda a acercar el cuidado ambiental a más personas.", puntos: 50, criterio: "Publicar 5 artículos aprobados." },
        { medalla_id: "medalla-explorador", nombre: "Explorador", icono: "🔬", descripcion: "Has participado en diversas formas de cuidado ambiental.", puntos: 30, criterio: "Completar actividades de 3 categorías diferentes." },
        { medalla_id: "medalla-regenera", nombre: "Regenera BCS", icono: "🏆", descripcion: "Un reconocimiento especial por combinar participación y comunicación.", puntos: 100, criterio: "Completar 10 voluntariados y publicar 5 artículos." }
    ];
    const coastalCategories = new Set(["limpieza", "conservación"]);

    const demoUsers = [
        { usuario_id: "usr-admin", nombre: "Administración Regenera", correo: "admin@regenera.test", password: "demo123", foto: "", rol: "administrador", organizadorVerificado: false, fechaRegistro: "2026-01-01T09:00:00.000Z", estado: "activo" },
        { usuario_id: "usr-user", nombre: "María Demo", correo: "usuario@regenera.test", password: "demo123", foto: "", rol: "usuario", organizadorVerificado: false, fechaRegistro: "2026-01-02T09:00:00.000Z", estado: "activo" },
        { usuario_id: "usr-organizer", nombre: "Carlos Organizador", correo: "organizador@regenera.test", password: "demo123", foto: "", rol: "organizador", organizadorVerificado: true, fechaRegistro: "2026-01-03T09:00:00.000Z", estado: "activo" }
    ];
    const demoPlaces = [
        { lugares_id: "lugar-balandra", nombre: "Balandra", descripcion: "Área costera y natural al norte de La Paz.", tipo: "playa", ubicacion: { nombre: "Balandra", direccion: "Carretera a Pichilingue, La Paz, BCS", latitud: 24.321, longitud: -110.327 }, informacionConservacion: "Respetar los accesos, no dejar residuos y seguir las indicaciones del área.", imagenes: [], fuenteInformacion: "Regenera BCS", fechaActualizacion: "2026-01-01T09:00:00.000Z", estado: "activo" },
        { lugares_id: "lugar-manglito", nombre: "El Manglito", descripcion: "Zona costera y comunidad pesquera de La Paz.", tipo: "comunidad", ubicacion: { nombre: "El Manglito", direccion: "La Paz, Baja California Sur", latitud: 24.134, longitud: -110.318 }, informacionConservacion: "Cuidar los espacios comunitarios y el entorno marino.", imagenes: [], fuenteInformacion: "Regenera BCS", fechaActualizacion: "2026-01-01T09:00:00.000Z", estado: "activo" },
        { lugares_id: "lugar-mogote", nombre: "El Mogote", descripcion: "Península y zona de humedales frente a La Paz.", tipo: "humedal", ubicacion: { nombre: "El Mogote", direccion: "Bahía de La Paz, Baja California Sur", latitud: 24.155, longitud: -110.383 }, informacionConservacion: "Evitar perturbar humedales y fauna silvestre.", imagenes: [], fuenteInformacion: "Regenera BCS", fechaActualizacion: "2026-01-01T09:00:00.000Z", estado: "activo" },
        { lugares_id: "lugar-todos-santos", nombre: "Todos Santos", descripcion: "Localidad y entorno natural del municipio de La Paz.", tipo: "localidad", ubicacion: { nombre: "Todos Santos", direccion: "Todos Santos, Baja California Sur", latitud: 23.446, longitud: -110.224 }, informacionConservacion: "Respetar las áreas naturales y las comunidades locales.", imagenes: [], fuenteInformacion: "Regenera BCS", fechaActualizacion: "2026-01-01T09:00:00.000Z", estado: "activo" }
    ];
    const demoVolunteers = [
        { voluntariados_id: "vol-01", organizador_id: "usr-organizer", lugar_id: "lugar-balandra", titulo: "Limpieza de playa", descripcion: "Jornada comunitaria para retirar residuos y proteger los ecosistemas costeros.", categoria: "limpieza", imagen: "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1000&q=85", fecha: "2026-10-10", horarioInicio: "08:00", horarioFin: "11:00", ubicacion: "Acceso principal de Balandra", capacidad: 30, requisitos: "Llevar agua reutilizable y protección solar.", materiales: "Guantes y bolsas para residuos.", estado: "publicado", fechaCreacion: "2026-08-01T09:00:00.000Z", fechaActualizacion: "2026-08-01T09:00:00.000Z" },
        { voluntariados_id: "vol-02", organizador_id: "usr-organizer", lugar_id: "lugar-mogote", titulo: "Restauración costera", descripcion: "Participa en actividades para recuperar espacios naturales y fortalecer su conservación.", categoria: "restauración", imagen: "https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1000&q=85", fecha: "2026-10-14", horarioInicio: "07:30", horarioFin: "10:30", ubicacion: "Zona de trabajo El Mogote", capacidad: 20, requisitos: "Calzado cerrado y disposición para trabajo en exterior.", materiales: "Herramientas proporcionadas por la organización.", estado: "publicado", fechaCreacion: "2026-08-02T09:00:00.000Z", fechaActualizacion: "2026-08-02T09:00:00.000Z" },
        { voluntariados_id: "vol-03", organizador_id: "usr-organizer", lugar_id: "lugar-todos-santos", titulo: "Conservación de especies", descripcion: "Colabora en una iniciativa enfocada en proteger la biodiversidad local.", categoria: "conservación", imagen: "https://images.unsplash.com/photo-1568430462989-44163eb1752f?auto=format&fit=crop&w=1000&q=85", fecha: "2026-10-18", horarioInicio: "07:00", horarioFin: "10:00", ubicacion: "Punto de encuentro Todos Santos", capacidad: 25, requisitos: "Seguir las indicaciones del equipo de conservación.", materiales: "Libreta de campo opcional.", estado: "publicado", fechaCreacion: "2026-08-03T09:00:00.000Z", fechaActualizacion: "2026-08-03T09:00:00.000Z" },
        { voluntariados_id: "vol-04", organizador_id: "usr-organizer", lugar_id: "lugar-manglito", titulo: "Monitoreo de fauna marina", descripcion: "Jornada de observación y registro responsable de fauna marina.", categoria: "monitoreo", imagen: "https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=1000&q=85", fecha: "2026-10-25", horarioInicio: "07:00", horarioFin: "09:30", ubicacion: "Malecón de El Manglito", capacidad: 15, requisitos: "No se requiere experiencia previa.", materiales: "Formato de observación y lápiz.", estado: "publicado", fechaCreacion: "2026-08-04T09:00:00.000Z", fechaActualizacion: "2026-08-04T09:00:00.000Z" },
        { voluntariados_id: "vol-05", organizador_id: "usr-organizer", lugar_id: "lugar-manglito", titulo: "Recuperación de manglar", descripcion: "Actividad propuesta para recuperar un espacio natural costero.", categoria: "reforestación", imagen: "https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=1000&q=85", fecha: "2026-11-02", horarioInicio: "08:00", horarioFin: "12:00", ubicacion: "El Manglito", capacidad: 18, requisitos: "Asistir a la charla de seguridad.", materiales: "Plantas nativas y herramientas.", estado: "pendiente", fechaCreacion: "2026-08-05T09:00:00.000Z", fechaActualizacion: "2026-08-05T09:00:00.000Z" },
        { voluntariados_id: "vol-06", organizador_id: "usr-organizer", lugar_id: "lugar-todos-santos", titulo: "Educación ambiental comunitaria", descripcion: "Taller comunitario para compartir prácticas de cuidado del entorno.", categoria: "educación ambiental", imagen: "https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1000&q=85", fecha: "2026-09-10", horarioInicio: "09:00", horarioFin: "11:00", ubicacion: "Centro comunitario", capacidad: 20, requisitos: "Ninguno.", materiales: "Material educativo.", estado: "finalizado", fechaCreacion: "2026-07-20T09:00:00.000Z", fechaActualizacion: "2026-09-10T15:00:00.000Z" }
    ];
    const demoArticles = [
        { articulos_id: "art-01", autor_id: "usr-organizer", titulo: "Nuevas iniciativas para cuidar nuestras costas", contenido: "Las comunidades de Baja California Sur impulsan iniciativas para mantener limpias las playas, reducir los residuos y cuidar los ecosistemas costeros.\n\nLa participación local ayuda a identificar las necesidades de cada lugar y a sostener acciones de conservación a largo plazo.", imagen: "https://images.unsplash.com/photo-1516690561799-46d8f74f9abf?auto=format&fit=crop&w=1200&q=85", categoria: "comunidad", estado: "publicado", fechaPublicacion: "2026-08-12T10:00:00.000Z", fechaActualizacion: "2026-08-12T10:00:00.000Z" },
        { articulos_id: "art-02", autor_id: "usr-admin", titulo: "Protegiendo los ecosistemas de BCS", contenido: "Los ecosistemas sudcalifornianos conectan ambientes desérticos, costeros y marinos. Cada uno alberga especies y procesos naturales que pueden verse afectados por actividades humanas.\n\nAl visitar áreas naturales, permanece en los senderos establecidos, evita dejar residuos y respeta la fauna.", imagen: "https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1200&q=85", categoria: "naturaleza", estado: "publicado", fechaPublicacion: "2026-08-16T10:00:00.000Z", fechaActualizacion: "2026-08-16T10:00:00.000Z" },
        { articulos_id: "art-03", autor_id: "usr-organizer", titulo: "¿Qué significa viajar responsablemente?", contenido: "Viajar responsablemente implica considerar el efecto de nuestras decisiones en el entorno y en las personas que habitan cada destino.\n\nPlanear las visitas, consumir en negocios locales y reducir el uso de desechables ayuda a que el turismo contribuya positivamente.", imagen: "https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1200&q=85", categoria: "turismo", estado: "publicado", fechaPublicacion: "2026-08-20T10:00:00.000Z", fechaActualizacion: "2026-08-20T10:00:00.000Z" },
        { articulos_id: "art-04", autor_id: "usr-user", titulo: "Jóvenes por el cuidado del agua", contenido: "Una propuesta comunitaria para conversar sobre el cuidado del agua y los ecosistemas que dependen de ella.", imagen: "https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85", categoria: "comunidad", estado: "pendiente", fechaPublicacion: null, fechaActualizacion: "2026-08-22T10:00:00.000Z" },
        { articulos_id: "art-05", autor_id: "usr-organizer", titulo: "Buenas prácticas en áreas naturales", contenido: "Recomendaciones para visitar áreas naturales de Baja California Sur y reducir el impacto de nuestras actividades.", imagen: "https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=1200&q=85", categoria: "conservación", estado: "pendiente", fechaPublicacion: null, fechaActualizacion: "2026-08-23T10:00:00.000Z" }
    ];
    const demoFlora = [
        { floraFauna_id: "ff-01", nombre: "Ballena gris", nombreCientifico: "Eschrichtius robustus", tipo: "fauna", descripcion: "Mamífero marino migratorio que visita las lagunas y costas de la península.", habitat: "Aguas costeras y lagunas de reproducción.", estadoConservacion: "Preocupación menor", imagenes: ["https://images.unsplash.com/photo-1568430462989-44163eb1752f?auto=format&fit=crop&w=1200&q=85"], ubicacion: "Costas de Baja California Sur" },
        { floraFauna_id: "ff-02", nombre: "Tortuga marina", nombreCientifico: "Cheloniidae", tipo: "fauna", descripcion: "Grupo de reptiles marinos que utiliza las costas para alimentarse y anidar.", habitat: "Océano y playas de anidación.", estadoConservacion: "Amenazada", imagenes: ["https://images.unsplash.com/photo-1437622368342-7a3d73a34c8f?auto=format&fit=crop&w=1200&q=85"], ubicacion: "Litoral sudcaliforniano" },
        { floraFauna_id: "ff-03", nombre: "Cardón", nombreCientifico: "Pachycereus pringlei", tipo: "flora", descripcion: "Cactus columnar emblemático del desierto peninsular.", habitat: "Matorral xerófilo y desierto.", estadoConservacion: "Preocupación menor", imagenes: ["https://images.unsplash.com/photo-1509316785289-025f5b846b35?auto=format&fit=crop&w=1200&q=85"], ubicacion: "Península de Baja California" },
        { floraFauna_id: "ff-04", nombre: "Lobo marino de California", nombreCientifico: "Zalophus californianus", tipo: "fauna", descripcion: "Mamífero marino que descansa y se reproduce en islas y zonas rocosas.", habitat: "Islas y costas rocosas.", estadoConservacion: "Preocupación menor", imagenes: ["https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=1200&q=85"], ubicacion: "Mar de Cortés" },
        { floraFauna_id: "ff-05", nombre: "Manglar rojo", nombreCientifico: "Rhizophora mangle", tipo: "flora", descripcion: "Árbol costero que forma refugios y ayuda a proteger las orillas.", habitat: "Lagunas costeras y estuarios.", estadoConservacion: "Protección especial", imagenes: ["https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=1200&q=85"], ubicacion: "Humedales costeros de BCS" },
        { floraFauna_id: "ff-06", nombre: "Ballena azul", nombreCientifico: "Balaenoptera musculus", tipo: "fauna", descripcion: "El animal más grande del planeta y visitante de las aguas del Pacífico.", habitat: "Aguas abiertas y zonas de alimentación.", estadoConservacion: "En peligro", imagenes: ["https://images.unsplash.com/photo-1568430462989-44163eb1752f?auto=format&fit=crop&w=1200&q=85"], ubicacion: "Pacífico sudcaliforniano" }
    ];

    function createId(prefix) {
        generatedId += 1;
        return `${prefix}-${Date.now().toString(36)}-${generatedId.toString(36)}`;
    }

    function readKey(key, fallback = []) {
        const raw = localStorage.getItem(key);
        if (raw === null) return fallback;
        try {
            const value = JSON.parse(raw);
            if (!Array.isArray(value) && typeof value !== "object") throw new Error("Formato JSON inesperado.");
            return value;
        } catch (error) {
            console.error(`No se pudo leer ${key} desde localStorage.`, error);
            throw error;
        }
    }

    function writeKey(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error(`No se pudo guardar ${key} en localStorage.`, error);
            throw error;
        }
    }

    function getCollection(name) {
        if (!keys[name] || name === "sesion") throw new Error(`Colección no válida: ${name}`);
        const rows = readKey(keys[name]);
        if (!Array.isArray(rows)) throw new Error(`La colección ${name} debe ser una lista.`);
        return rows;
    }

    function saveCollection(name, rows) {
        if (!keys[name] || name === "sesion" || !Array.isArray(rows)) throw new Error(`No se puede guardar la colección ${name}.`);
        writeKey(keys[name], rows);
        return rows;
    }

    function getById(name, id) {
        const primaryKeys = {
            usuarios: "usuario_id",
            voluntariados: "voluntariados_id",
            inscripciones: "inscripciones_id",
            asistencias: "asistencia_id",
            articulos: "articulos_id",
            notificaciones: "notificaciones_id",
            auditoria: "auditoria_id",
            lugares: "lugares_id",
            floraFauna: "floraFauna_id",
            medallas: "medalla_id",
            usuarioMedallas: "usuario_medalla_id",
            puntos: "puntos_id",
            codigosAsistencia: "codigo_asistencia_id"
        };
        const primaryKey = primaryKeys[name];
        return primaryKey ? getCollection(name).find(row => String(row[primaryKey]) === String(id)) : undefined;
    }

    function initializeDemoData() {
        const seeds = {
            usuarios: demoUsers,
            lugares: demoPlaces,
            voluntariados: demoVolunteers,
            articulos: demoArticles,
            floraFauna: demoFlora,
            inscripciones: [
                { inscripciones_id: "ins-01", usuario_id: "usr-user", voluntariado_id: "vol-01", fechaInscripciones: "2026-08-10T09:00:00.000Z", estado: "activa" },
                { inscripciones_id: "ins-02", usuario_id: "usr-user", voluntariado_id: "vol-02", fechaInscripciones: "2026-08-11T09:00:00.000Z", estado: "completada" },
                { inscripciones_id: "ins-03", usuario_id: "usr-organizer", voluntariado_id: "vol-01", fechaInscripciones: "2026-08-12T09:00:00.000Z", estado: "activa" },
                { inscripciones_id: "ins-04", usuario_id: "usr-organizer", voluntariado_id: "vol-02", fechaInscripciones: "2026-08-12T09:00:00.000Z", estado: "completada" },
                { inscripciones_id: "ins-05", usuario_id: "usr-admin", voluntariado_id: "vol-01", fechaInscripciones: "2026-08-13T09:00:00.000Z", estado: "activa" },
                { inscripciones_id: "ins-06", usuario_id: "usr-user", voluntariado_id: "vol-04", fechaInscripciones: "2026-08-14T09:00:00.000Z", estado: "activa" }
            ],
            asistencias: [
                { asistencia_id: "asis-01", usuario_id: "usr-user", voluntariado_id: "vol-02", fecha: "2026-08-11", hora: "10:30", metodo: "QR", confirmada: true },
                { asistencia_id: "asis-02", usuario_id: "usr-organizer", voluntariado_id: "vol-02", fecha: "2026-08-11", hora: "10:35", metodo: "QR", confirmada: true }
            ],
            notificaciones: [
                { notificaciones_id: "not-01", usuario_id: "usr-user", tipo: "inscripcion", titulo: "Inscripción confirmada", mensaje: "Tu lugar en Restauración costera está registrado.", entidad: { tipo: "voluntariado", id: "vol-02" }, prioridad: "normal", leido: false, fechaCreacion: "2026-08-11T09:00:00.000Z", fechaLectura: null, fechaExpiracion: null, datos: {} },
                { notificaciones_id: "not-02", usuario_id: "usr-organizer", tipo: "contenido", titulo: "Artículo aprobado", mensaje: "Tu artículo sobre iniciativas costeras fue publicado.", entidad: { tipo: "articulo", id: "art-01" }, prioridad: "normal", leido: true, fechaCreacion: "2026-08-12T10:00:00.000Z", fechaLectura: "2026-08-12T10:05:00.000Z", fechaExpiracion: null, datos: {} }
            ],
            auditoria: [
                { auditoria_id: "audit-01", usuario_id: "usr-admin", accion: "ADMIN_PUBLICO_ARTICULO", entidad: "articulos", entidad_id: "art-01", fecha: "2026-08-12T10:00:00.000Z", ip: "demo", detalles: "Publicación inicial de demostración." }
            ],
            medallas: demoMedals,
            usuarioMedallas: [],
            puntos: [],
            codigosAsistencia: []
        };

        Object.entries(seeds).forEach(([name, rows]) => {
            if (localStorage.getItem(keys[name]) === null) writeKey(keys[name], rows);
        });

        if (localStorage.getItem(keys.sesion) === null) {
            const legacy = localStorage.getItem("usuarioRegenera");
            if (legacy) {
                try {
                    const oldUser = JSON.parse(legacy);
                    const users = getCollection("usuarios");
                    let user = users.find(row => row.correo.toLowerCase() === String(oldUser.correo || "").toLowerCase());
                    if (!user && oldUser.correo) {
                        user = {
                            usuario_id: createId("usr"),
                            nombre: String(oldUser.nombre || "Usuario"),
                            correo: String(oldUser.correo).toLowerCase(),
                            password: "",
                            foto: "",
                            rol: "usuario",
                            organizadorVerificado: false,
                            fechaRegistro: new Date().toISOString(),
                            estado: "activo"
                        };
                        users.push(user);
                        saveCollection("usuarios", users);
                    }
                    if (user) writeKey(keys.sesion, { usuario_id: user.usuario_id, fechaInicio: new Date().toISOString() });
                } catch (error) {
                    console.error("No se pudo migrar la sesión anterior.", error);
                    writeKey(keys.sesion, null);
                }
            } else {
                writeKey(keys.sesion, null);
            }
        }

        migrateLegacyContent();
        synchronizeExistingRewards();
    }

    function migrateLegacyContent() {
        const legacyMappings = [
            ["articulosRegenera", "articulos"],
            ["voluntariadosRegenera", "voluntariados"]
        ];
        legacyMappings.forEach(([oldKey, name]) => {
            const raw = localStorage.getItem(oldKey);
            if (!raw) return;
            try {
                const legacyRows = JSON.parse(raw);
                if (!Array.isArray(legacyRows)) throw new Error(`${oldKey} no contiene una lista.`);
                const rows = getCollection(name);
                const users = getCollection("usuarios");
                const author = users.find(user => user.usuario_id === (getSession()?.usuario_id || "usr-admin")) || users[0];
                legacyRows.forEach((item, index) => {
                    const id = `migrado-${name}-${item.id ?? index}`;
                    if (rows.some(row => row.articulos_id === id || row.voluntariados_id === id)) return;
                    if (name === "articulos") {
                        rows.push({
                            articulos_id: id,
                            autor_id: author.usuario_id,
                            titulo: String(item.title || item.titulo || "Artículo"),
                            contenido: String(item.content || item.descripcion || item.summary || ""),
                            imagen: item.image || "",
                            categoria: String(item.category || item.categoria || "comunidad").toLowerCase(),
                            estado: "pendiente",
                            fechaPublicacion: null,
                            fechaActualizacion: new Date().toISOString()
                        });
                    } else {
                        const place = getCollection("lugares").find(row => row.nombre === (item.place || item.lugar)) || getCollection("lugares")[0];
                        rows.push({
                            voluntariados_id: id,
                            organizador_id: author.usuario_id,
                            lugar_id: place.lugares_id,
                            titulo: String(item.title || item.titulo || "Voluntariado"),
                            descripcion: String(item.content || item.descripcion || item.summary || ""),
                            categoria: normalizeCategory(item.category || item.categoria),
                            imagen: item.image || "",
                            fecha: String(item.date || item.fecha || ""),
                            horarioInicio: String(item.hour || item.hora || ""),
                            horarioFin: "",
                            ubicacion: item.place || item.lugar || place.nombre,
                            capacidad: Number(item.capacity || item.cupo || 20),
                            requisitos: "",
                            materiales: "",
                            estado: "pendiente",
                            fechaCreacion: new Date().toISOString(),
                            fechaActualizacion: new Date().toISOString()
                        });
                    }
                });
                saveCollection(name, rows);
            } catch (error) {
                console.error(`No se pudo migrar ${oldKey}.`, error);
            }
        });

        const oldParticipations = localStorage.getItem("participacionesRegenera");
        const session = getSession();
        if (!oldParticipations || !session) return;
        try {
            const ids = JSON.parse(oldParticipations);
            if (!Array.isArray(ids)) throw new Error("Las inscripciones anteriores no son una lista.");
            const inscriptions = getCollection("inscripciones");
            ids.forEach(id => {
                const volunteer = getCollection("voluntariados").find(row => row.voluntariados_id === `vol-${String(id).padStart(2, "0")}`);
                if (!volunteer || inscriptions.some(row => row.usuario_id === session.usuario_id && row.voluntariado_id === volunteer.voluntariados_id)) return;
                inscriptions.push({ inscripciones_id: createId("ins"), usuario_id: session.usuario_id, voluntariado_id: volunteer.voluntariados_id, fechaInscripciones: new Date().toISOString(), estado: "activa" });
            });
            saveCollection("inscripciones", inscriptions);
        } catch (error) {
            console.error("No se pudieron migrar las inscripciones anteriores.", error);
        }
    }

    function getSession() {
        const session = readKey(keys.sesion, null);
        return session && typeof session === "object" && !Array.isArray(session) ? session : null;
    }

    function getCurrentUser() {
        const session = getSession();
        if (!session) return null;
        const user = getById("usuarios", session.usuario_id);
        return user && user.estado === "activo" ? user : null;
    }

    function setSession(user) {
        writeKey(keys.sesion, { usuario_id: user.usuario_id, fechaInicio: new Date().toISOString() });
        renderNavigation();
        return user;
    }

    function logout() {
        writeKey(keys.sesion, null);
        renderNavigation();
        window.location.href = "index.html";
    }

    function login(email, password) {
        const user = getCollection("usuarios").find(row => row.correo.toLowerCase() === email.trim().toLowerCase());
        if (!user) return { ok: false, message: "No encontramos una cuenta con ese correo." };
        if (user.estado !== "activo") return { ok: false, message: "Esta cuenta está inactiva. Contacta al administrador." };
        if (user.password !== password) return { ok: false, message: "La contraseña no coincide." };
        setSession(user);
        audit(user.usuario_id, "INICIO_SESION", "usuarios", user.usuario_id, "Inicio de sesión de demostración.");
        return { ok: true, user };
    }

    function register(name, email, password) {
        const users = getCollection("usuarios");
        if (users.some(user => user.correo.toLowerCase() === email.trim().toLowerCase())) {
            return { ok: false, message: "Ya existe una cuenta con ese correo." };
        }
        const user = {
            usuario_id: createId("usr"),
            nombre: name.trim(),
            correo: email.trim().toLowerCase(),
            password,
            foto: "",
            rol: "usuario",
            organizadorVerificado: false,
            fechaRegistro: new Date().toISOString(),
            estado: "activo"
        };
        users.push(user);
        saveCollection("usuarios", users);
        setSession(user);
        audit(user.usuario_id, "REGISTRO_USUARIO", "usuarios", user.usuario_id, "Alta desde el formulario de registro.");
        return { ok: true, user };
    }

    function requireUser() {
        const user = getCurrentUser();
        if (!user) throw new Error("Inicia sesión para realizar esta acción.");
        return user;
    }

    function createArticle(input) {
        const user = requireUser();
        const articles = getCollection("articulos");
        const now = new Date().toISOString();
        const publishDirectly = user.rol === "administrador" && input.estadoInicial === "publicado";
        const article = {
            articulos_id: createId("art"),
            autor_id: user.usuario_id,
            titulo: String(input.titulo || "").trim(),
            contenido: String(input.contenido || "").trim(),
            imagen: String(input.imagen || ""),
            categoria: String(input.categoria || "").trim().toLowerCase(),
            estado: publishDirectly ? "publicado" : "pendiente",
            fechaPublicacion: publishDirectly ? now : null,
            fechaActualizacion: now
        };
        if (!article.titulo || !article.contenido || !article.categoria) throw new Error("Completa el título, la categoría y el contenido.");
        articles.unshift(article);
        saveCollection("articulos", articles);
        addNotification(user.usuario_id, publishDirectly ? "contenido" : "moderacion", publishDirectly ? "Artículo publicado" : "Artículo enviado a revisión", publishDirectly ? `“${article.titulo}” ya es público.` : `“${article.titulo}” está pendiente de aprobación.`, { tipo: "articulos", id: article.articulos_id });
        audit(user.usuario_id, publishDirectly ? "ADMIN_CREA_ARTICULO_PUBLICADO" : "CREA_ARTICULO", "articulos", article.articulos_id, publishDirectly ? "Creó y publicó el artículo." : "Artículo enviado a moderación.");
        if (publishDirectly) {
            awardPoints(user.usuario_id, 25, "Artículo aprobado", "articulos", article.articulos_id);
            checkUserAchievements(user.usuario_id);
        }
        return article;
    }

    function createVolunteer(input) {
        const user = requireUser();
        if (user.rol === "organizador" && !user.organizadorVerificado) throw new Error("Tu cuenta de organizador está pendiente de verificación.");
        const place = getById("lugares", input.lugar_id);
        if (!place || place.estado !== "activo") throw new Error("Selecciona un lugar activo.");
        const volunteers = getCollection("voluntariados");
        const now = new Date().toISOString();
        const volunteer = {
            voluntariados_id: createId("vol"),
            organizador_id: user.usuario_id,
            lugar_id: place.lugares_id,
            titulo: String(input.titulo || "").trim(),
            descripcion: String(input.descripcion || "").trim(),
            categoria: normalizeCategory(input.categoria),
            imagen: String(input.imagen || ""),
            fecha: String(input.fecha || ""),
            horarioInicio: String(input.horarioInicio || ""),
            horarioFin: String(input.horarioFin || ""),
            ubicacion: String(input.ubicacion || "").trim(),
            capacidad: Number(input.capacidad),
            requisitos: String(input.requisitos || "").trim(),
            materiales: String(input.materiales || "").trim(),
            estado: user.rol === "administrador" && input.estadoInicial === "publicado" ? "publicado" : "pendiente",
            fechaCreacion: now,
            fechaActualizacion: now
        };
        if (!volunteer.titulo || !volunteer.descripcion || !volunteer.fecha || !volunteer.horarioInicio || !volunteer.horarioFin || !volunteer.ubicacion || !Number.isInteger(volunteer.capacidad) || volunteer.capacidad < 1) {
            throw new Error("Completa todos los datos obligatorios del voluntariado.");
        }
        volunteers.unshift(volunteer);
        saveCollection("voluntariados", volunteers);
        const publishedDirectly = volunteer.estado === "publicado";
        addNotification(user.usuario_id, publishedDirectly ? "contenido" : "moderacion", publishedDirectly ? "Voluntariado publicado" : "Voluntariado enviado a revisión", publishedDirectly ? `“${volunteer.titulo}” ya es público.` : `“${volunteer.titulo}” está pendiente de aprobación.`, { tipo: "voluntariado", id: volunteer.voluntariados_id });
        audit(user.usuario_id, publishedDirectly ? "ADMIN_CREA_VOLUNTARIADO_PUBLICADO" : "CREA_VOLUNTARIADO", "voluntariados", volunteer.voluntariados_id, publishedDirectly ? "Creó y publicó el voluntariado." : "Voluntariado enviado a moderación.");
        return volunteer;
    }

    function updateArticle(id, input) {
        const user = requireUser();
        const rows = getCollection("articulos");
        const item = rows.find(row => row.articulos_id === id);
        if (!item || user.rol !== "administrador" && item.autor_id !== user.usuario_id) throw new Error("No tienes permiso para editar este artículo.");
        const wasPublished = item.estado === "publicado";
        const title = String(input.titulo || "").trim();
        const category = String(input.categoria || "").trim().toLowerCase();
        const content = String(input.contenido || "").trim();
        if (!title || !category || !content) throw new Error("Completa el título, la categoría y el contenido.");
        item.titulo = title;
        item.categoria = category;
        item.contenido = content;
        item.imagen = String(input.imagen || "");
        item.fechaActualizacion = new Date().toISOString();
        if (user.rol !== "administrador" && wasPublished) {
            item.estado = "pendiente";
            item.fechaPublicacion = null;
        } else if (user.rol === "administrador" && ["pendiente", "publicado"].includes(input.estadoInicial)) {
            item.estado = input.estadoInicial;
            if (item.estado === "publicado" && !item.fechaPublicacion) item.fechaPublicacion = item.fechaActualizacion;
        }
        saveCollection("articulos", rows);
        addNotification(item.autor_id, "moderacion", "Artículo actualizado", item.estado === "pendiente" ? `“${item.titulo}” está pendiente de revisión.` : `“${item.titulo}” fue actualizado.`, { tipo: "articulos", id });
        audit(user.usuario_id, "ACTUALIZA_ARTICULO", "articulos", id, `Actualizó “${item.titulo}”.`);
        if (item.estado === "publicado" && !wasPublished) {
            awardPoints(item.autor_id, 25, "Artículo aprobado", "articulos", id);
            checkUserAchievements(item.autor_id);
        }
        return item;
    }

    function updateVolunteer(id, input) {
        const user = requireUser();
        const rows = getCollection("voluntariados");
        const item = rows.find(row => row.voluntariados_id === id);
        if (!item || user.rol !== "administrador" && item.organizador_id !== user.usuario_id) throw new Error("No tienes permiso para editar este voluntariado.");
        if (["cancelado", "finalizado"].includes(item.estado)) throw new Error("Las actividades canceladas o finalizadas no se pueden editar.");
        const place = getById("lugares", input.lugar_id);
        if (!place || place.estado !== "activo") throw new Error("Selecciona un lugar activo.");
        const capacity = Number(input.capacidad);
        const activeCount = getCollection("inscripciones").filter(row => row.voluntariado_id === id && ["activa", "completada"].includes(row.estado)).length;
        if (!Number.isInteger(capacity) || capacity < 1 || capacity < activeCount) throw new Error("El cupo debe ser positivo y no menor que las inscripciones actuales.");
        const title = String(input.titulo || "").trim();
        const description = String(input.descripcion || "").trim();
        if (!title || !description || !input.fecha || !input.horarioInicio || !input.horarioFin) throw new Error("Completa el título, la descripción, la fecha y el horario.");
        if (String(input.fecha) < new Date().toISOString().slice(0, 10)) throw new Error("La fecha de la actividad debe ser futura.");
        const previousState = item.estado;
        Object.assign(item, {
            titulo: title,
            lugar_id: place.lugares_id,
            categoria: normalizeCategory(input.categoria),
            descripcion: description,
            imagen: String(input.imagen || ""),
            fecha: String(input.fecha),
            horarioInicio: String(input.horarioInicio),
            horarioFin: String(input.horarioFin),
            ubicacion: String(input.ubicacion || "").trim(),
            capacidad: capacity,
            requisitos: String(input.requisitos || "").trim(),
            materiales: String(input.materiales || "").trim(),
            fechaActualizacion: new Date().toISOString()
        });
        if (user.rol !== "administrador" && previousState === "publicado") item.estado = "pendiente";
        else if (user.rol === "administrador" && ["pendiente", "publicado"].includes(input.estadoInicial)) item.estado = input.estadoInicial;
        saveCollection("voluntariados", rows);
        const activeParticipants = getCollection("inscripciones").filter(row => row.voluntariado_id === id && row.estado === "activa");
        activeParticipants.forEach(row => addNotification(row.usuario_id, "actualizacion", "Voluntariado actualizado", `“${item.titulo}” fue actualizado. Revisa la fecha, ubicación y requisitos.`, { tipo: "voluntariado", id }));
        addNotification(item.organizador_id, "moderacion", "Voluntariado actualizado", item.estado === "pendiente" ? `“${item.titulo}” está pendiente de revisión.` : `“${item.titulo}” fue actualizado.`, { tipo: "voluntariado", id });
        audit(user.usuario_id, "ACTUALIZA_VOLUNTARIADO", "voluntariados", id, `Actualizó “${item.titulo}”.`);
        return item;
    }

    function moderateContent(type, id, state) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede moderar contenido.");
        const config = {
            voluntariados: { pk: "voluntariados_id", states: ["publicado", "rechazado", "cancelado", "finalizado"], owner: "organizador_id", name: "voluntariado" },
            articulos: { pk: "articulos_id", states: ["publicado", "pendiente", "rechazado", "retirado"], owner: "autor_id", name: "artículo" }
        }[type];
        if (!config || !config.states.includes(state)) throw new Error("El estado solicitado no es válido.");
        const rows = getCollection(type);
        const item = rows.find(row => row[config.pk] === id);
        if (!item) throw new Error("No se encontró el contenido solicitado.");
        const previousState = item.estado;
        item.estado = state;
        item.fechaActualizacion = new Date().toISOString();
        if (type === "articulos" && state === "publicado" && !item.fechaPublicacion) item.fechaPublicacion = item.fechaActualizacion;
        saveCollection(type, rows);
        const messages = {
            publicado: `Tu ${config.name} “${item.titulo}” fue publicado.`,
            pendiente: `Tu ${config.name} “${item.titulo}” volvió a revisión.`,
            rechazado: `Tu ${config.name} “${item.titulo}” fue rechazado.`,
            retirado: `Tu ${config.name} “${item.titulo}” fue retirado de la publicación.`,
            cancelado: `Tu voluntariado “${item.titulo}” fue cancelado.`,
            finalizado: `Tu voluntariado “${item.titulo}” fue marcado como finalizado.`
        };
        addNotification(item[config.owner], "moderacion", "Actualización de contenido", messages[state], { tipo: type === "articulos" ? "articulo" : "voluntariado", id });
        const action = {
            articulos: { publicado: "ADMIN_PUBLICO_ARTICULO", pendiente: "ADMIN_DEVUELVE_ARTICULO_A_REVISION", rechazado: "ADMIN_RECHAZO_ARTICULO", retirado: "ADMIN_RETIRA_ARTICULO" },
            voluntariados: { publicado: "ADMIN_PUBLICO_VOLUNTARIADO", rechazado: "ADMIN_RECHAZA_VOLUNTARIADO", cancelado: "ADMIN_CANCELA_VOLUNTARIADO", finalizado: "ADMIN_FINALIZA_VOLUNTARIADO" }
        }[type][state];
        audit(admin.usuario_id, action, type, id, `Estado actualizado a ${state}.`);
        if (type === "voluntariados" && state === "cancelado") {
            const inscriptions = getCollection("inscripciones");
            const affected = inscriptions.filter(row => row.voluntariado_id === id && row.estado === "activa");
            affected.forEach(row => {
                row.estado = "cancelada";
                row.fechaCancelacion = new Date().toISOString();
                addNotification(row.usuario_id, "cancelacion", "Voluntariado cancelado", `La actividad “${item.titulo}” fue cancelada.`, { tipo: "voluntariado", id });
            });
            if (affected.length) saveCollection("inscripciones", inscriptions);
        }
        if (type === "articulos" && state === "publicado" && previousState !== "publicado") {
            audit(admin.usuario_id, "ARTICULO_APROBADO", "articulos", id, `Aprobó el artículo “${item.titulo}”.`);
            awardPoints(item[config.owner], 25, "Artículo aprobado", "articulos", id);
            checkUserAchievements(item[config.owner]);
        }
        return item;
    }

    function createFlora(input) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede crear registros de flora/fauna.");
        const rows = getCollection("floraFauna");
        const item = {
            floraFauna_id: createId("ff"),
            nombre: String(input.nombre || "").trim(),
            nombreCientifico: String(input.nombreCientifico || "").trim(),
            tipo: input.tipo === "fauna" ? "fauna" : "flora",
            descripcion: String(input.descripcion || "").trim(),
            habitat: String(input.habitat || "").trim(),
            estadoConservacion: String(input.estadoConservacion || "Sin evaluar").trim(),
            imagenes: input.imagen ? [String(input.imagen)] : [],
            ubicacion: String(input.ubicacion || "").trim(),
            funcionEcologica: String(input.funcionEcologica || "").trim(),
            datoCurioso: String(input.datoCurioso || "").trim(),
            amenazas: String(input.amenazas || "").trim(),
            recomendaciones: String(input.recomendaciones || "").trim(),
            fuentes: Array.isArray(input.fuentes) ? input.fuentes.map(value => String(value).trim()).filter(Boolean) : []
        };
        if (!item.nombre || !item.descripcion) throw new Error("Completa el nombre y la descripción.");
        rows.push(item);
        saveCollection("floraFauna", rows);
        audit(admin.usuario_id, "ADMIN_CREA_FLORA_FAUNA", "floraFauna", item.floraFauna_id, `Creó ${item.nombre}.`);
        return item;
    }

    function removeFlora(id) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede gestionar flora/fauna.");
        const rows = getCollection("floraFauna");
        const item = rows.find(row => row.floraFauna_id === id);
        if (!item) throw new Error("No se encontró el registro de flora/fauna.");
        item.estado = "inactivo";
        saveCollection("floraFauna", rows);
        audit(admin.usuario_id, "ADMIN_DESACTIVA_FLORA_FAUNA", "floraFauna", id, `Desactivó ${item.nombre}.`);
    }

    function updateUser(id, changes) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede gestionar usuarios.");
        const users = getCollection("usuarios");
        const user = users.find(row => row.usuario_id === id);
        if (!user) throw new Error("No se encontró el usuario.");
        if (id === admin.usuario_id && (changes.rol && changes.rol !== "administrador" || changes.estado && changes.estado !== "activo")) {
            throw new Error("No puedes quitarte el rol ni desactivar tu propia cuenta.");
        }
        if (changes.rol && ["usuario", "organizador", "administrador"].includes(changes.rol)) {
            const oldRole = user.rol;
            if (oldRole !== changes.rol) {
                user.rol = changes.rol;
                audit(admin.usuario_id, "ADMIN_CAMBIO_ROL", "usuarios", id, `Rol cambiado de ${oldRole} a ${user.rol}.`);
            }
        }
        if (changes.solicitudOrganizadorEstado === "aprobada" && user.solicitudOrganizadorEstado !== "aprobada") {
            user.solicitudOrganizadorEstado = "aprobada";
            user.organizadorVerificado = true;
            user.fechaDecisionOrganizador = new Date().toISOString();
            user.fechaVerificacionOrganizador = user.fechaDecisionOrganizador;
            user.decididoPorOrganizador = admin.usuario_id;
            user.verificadoPor = admin.usuario_id;
            addNotification(user.usuario_id, "verificacion", "Solicitud aprobada", "Tu cuenta fue verificada para organizar voluntariados. El rol no cambia automáticamente.", { tipo: "perfil", id });
            audit(admin.usuario_id, "ADMIN_APRUEBA_SOLICITUD_ORGANIZADOR", "usuarios", id, "Aprobó la solicitud y verificó la cuenta sin cambiar su rol.");
        } else if (["pendiente", "rechazada", "sin solicitar"].includes(changes.solicitudOrganizadorEstado) && user.solicitudOrganizadorEstado !== changes.solicitudOrganizadorEstado) {
            user.solicitudOrganizadorEstado = changes.solicitudOrganizadorEstado;
            user.fechaDecisionOrganizador = changes.solicitudOrganizadorEstado === "pendiente" ? null : new Date().toISOString();
            user.decididoPorOrganizador = changes.solicitudOrganizadorEstado === "pendiente" ? null : admin.usuario_id;
            if (changes.solicitudOrganizadorEstado === "rechazada") user.organizadorVerificado = false;
            addNotification(user.usuario_id, "verificacion", "Solicitud de organizador actualizada", `Estado de solicitud: ${user.solicitudOrganizadorEstado}.`, { tipo: "perfil", id });
            audit(admin.usuario_id, "ADMIN_DECIDE_SOLICITUD_ORGANIZADOR", "usuarios", id, `Solicitud de organizador: ${user.solicitudOrganizadorEstado}.`);
        }
        if (typeof changes.organizadorVerificado === "boolean" && user.organizadorVerificado !== changes.organizadorVerificado) {
            user.organizadorVerificado = changes.organizadorVerificado;
            if (user.organizadorVerificado) {
                user.fechaVerificacionOrganizador = new Date().toISOString();
                user.verificadoPor = admin.usuario_id;
                user.solicitudOrganizadorEstado = "aprobada";
            } else {
                user.fechaRevocacionOrganizador = new Date().toISOString();
                user.verificadoPor = null;
            }
            audit(admin.usuario_id, user.organizadorVerificado ? "ADMIN_VERIFICA_ORGANIZADOR" : "ADMIN_RETIRA_VERIFICACION_ORGANIZADOR", "usuarios", id, `Verificación de organizador: ${user.organizadorVerificado}.`);
        }
        if (["activo", "inactivo"].includes(changes.estado) && user.estado !== changes.estado) {
            user.estado = changes.estado;
            audit(admin.usuario_id, "ADMIN_CAMBIO_ESTADO_USUARIO", "usuarios", id, `Estado de cuenta: ${user.estado}.`);
        }
        saveCollection("usuarios", users);
        renderNavigation();
        return user;
    }

    function requestOrganizerVerification() {
        const user = getCurrentUser();
        if (!user) return { ok: false, message: "Inicia sesión para solicitar la verificación." };
        if (user.rol !== "usuario") return { ok: false, message: "Esta solicitud está disponible para cuentas de usuario." };
        if (user.solicitudOrganizadorEstado === "pendiente") return { ok: false, message: "Tu solicitud ya está pendiente de revisión." };
        const users = getCollection("usuarios");
        const storedUser = users.find(row => row.usuario_id === user.usuario_id);
        storedUser.solicitudOrganizadorEstado = "pendiente";
        storedUser.fechaSolicitudOrganizador = new Date().toISOString();
        saveCollection("usuarios", users);
        getCollection("usuarios").filter(row => row.rol === "administrador" && row.estado === "activo")
            .forEach(admin => addNotification(admin.usuario_id, "verificacion", "Solicitud de organizador", `${user.nombre} solicitó verificar su cuenta de organizador.`, { tipo: "perfil", id: user.usuario_id }));
        audit(user.usuario_id, "SOLICITA_VERIFICACION_ORGANIZADOR", "usuarios", user.usuario_id, "Solicitó verificación como organizador.");
        return { ok: true, message: "Tu solicitud fue enviada para revisión." };
    }

    function removeContent(type, id) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede eliminar contenido.");
        const fields = { articulos: ["articulos_id", "ADMIN_ELIMINA_ARTICULO"], voluntariados: ["voluntariados_id", "ADMIN_ELIMINA_VOLUNTARIADO"] }[type];
        if (!fields) throw new Error("No se permite eliminar esta entidad desde esta vista.");
        const [pk, action] = fields;
        const rows = getCollection(type);
        const item = rows.find(row => row[pk] === id);
        if (!item) throw new Error("No se encontró el contenido.");
        if (type === "voluntariados") {
            const inscriptions = getCollection("inscripciones");
            const hasHistory = inscriptions.some(row => row.voluntariado_id === id)
                || getCollection("asistencias").some(row => row.voluntariado_id === id);
            if (hasHistory) {
                if (item.estado === "finalizado") throw new Error("Un voluntariado finalizado con historial no se puede eliminar.");
                if (item.estado === "cancelado") return item;
                return moderateContent("voluntariados", id, "cancelado");
            }
        }
        if (type === "articulos" && item.estado === "publicado") {
            return moderateContent("articulos", id, "retirado");
        }
        saveCollection(type, rows.filter(row => row[pk] !== id));
        audit(admin.usuario_id, action, type, id, `Eliminó “${item.titulo}”.`);
    }

    function updatePlace(id, changes) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede gestionar lugares.");
        const rows = getCollection("lugares");
        const place = rows.find(row => row.lugares_id === id);
        if (!place) throw new Error("No se encontró el lugar.");
        Object.assign(place, changes, { fechaActualizacion: new Date().toISOString() });
        saveCollection("lugares", rows);
        audit(admin.usuario_id, "ADMIN_ACTUALIZA_LUGAR", "lugares", id, `Actualizó el lugar ${place.nombre}.`);
        return place;
    }

    function createPlace(input) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede crear lugares.");
        const rows = getCollection("lugares");
        const now = new Date().toISOString();
        const latitude = input.latitud === "" || input.latitud == null ? null : Number(input.latitud);
        const longitude = input.longitud === "" || input.longitud == null ? null : Number(input.longitud);
        if (latitude != null && (!Number.isFinite(latitude) || latitude < -90 || latitude > 90)) throw new Error("La latitud debe estar entre -90 y 90.");
        if (longitude != null && (!Number.isFinite(longitude) || longitude < -180 || longitude > 180)) throw new Error("La longitud debe estar entre -180 y 180.");
        const place = {
            lugares_id: createId("lugar"),
            nombre: String(input.nombre || "").trim(),
            descripcion: String(input.descripcion || "").trim(),
            tipo: String(input.tipo || "otro"),
            ubicacion: {
                nombre: String(input.nombre || "").trim(),
                direccion: String(input.direccion || "").trim(),
                latitud: latitude,
                longitud: longitude
            },
            informacionConservacion: String(input.informacionConservacion || "").trim(),
            imagenes: [],
            fuenteInformacion: String(input.fuenteInformacion || "").trim(),
            fechaActualizacion: now,
            estado: "activo"
        };
        if (!place.nombre || !place.ubicacion.direccion) throw new Error("Completa el nombre y la dirección.");
        rows.push(place);
        saveCollection("lugares", rows);
        audit(admin.usuario_id, "ADMIN_CREA_LUGAR", "lugares", place.lugares_id, `Creó el lugar ${place.nombre}.`);
        return place;
    }

    function removePlace(id) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede gestionar lugares.");
        const place = getById("lugares", id);
        if (!place) throw new Error("No se encontró el lugar.");
        updatePlace(id, { estado: "inactivo" });
        return "inactivo";
    }

    function updateFlora(id, changes) {
        const admin = getCurrentUser();
        if (!admin || admin.rol !== "administrador") throw new Error("Solo administración puede gestionar flora y fauna.");
        const rows = getCollection("floraFauna");
        const item = rows.find(row => row.floraFauna_id === id);
        if (!item) throw new Error("No se encontró el registro de flora/fauna.");
        Object.assign(item, changes);
        saveCollection("floraFauna", rows);
        audit(admin.usuario_id, "ADMIN_ACTUALIZA_FLORA_FAUNA", "floraFauna", id, `Actualizó ${item.nombre}.`);
        return item;
    }

    function canManageVolunteer(user, volunteer) {
        return Boolean(user && volunteer && (user.rol === "administrador" || volunteer.organizador_id === user.usuario_id));
    }

    function addNotification(userId, type, title, message, entity, priority = "normal", data = {}) {
        const notifications = getCollection("notificaciones");
        const now = new Date().toISOString();
        const notification = {
            notificaciones_id: createId("not"),
            usuario_id: userId,
            tipo: type,
            titulo: title,
            mensaje: message,
            entidad: entity || null,
            prioridad: priority,
            leido: false,
            fechaCreacion: now,
            fechaLectura: null,
            fechaExpiracion: null,
            datos: data
        };
        notifications.unshift(notification);
        saveCollection("notificaciones", notifications);
        renderNotificationBadge();
        return notification;
    }

    function audit(userId, action, entity, entityId, details) {
        const records = getCollection("auditoria");
        records.unshift({
            auditoria_id: createId("audit"),
            usuario_id: userId || null,
            accion: action,
            entidad: entity,
            entidad_id: entityId || null,
            fecha: new Date().toISOString(),
            ip: "demo",
            detalles: details
        });
        saveCollection("auditoria", records);
    }

    function awardPoints(userId, amount, reason, entity, entityId, options = {}) {
        const key = `${userId}:${reason}:${entity}:${entityId}`;
        const points = getCollection("puntos");
        const existing = points.find(row => row.claveUnica === key);
        if (existing) return existing;
        const entry = {
            puntos_id: createId("pts"),
            usuario_id: userId,
            cantidad: amount,
            motivo: reason,
            entidad: entity,
            entidad_id: entityId,
            claveUnica: key,
            fecha: new Date().toISOString()
        };
        points.unshift(entry);
        saveCollection("puntos", points);
        if (options.audit !== false) audit(userId, "PUNTOS_OTORGADOS", entity, entityId, `+${amount} puntos: ${reason}.`);
        if (options.notify !== false) addNotification(userId, "puntos", "Puntos por participación", `Ganaste ${amount} puntos: ${reason}.`, { tipo: entity, id: entityId });
        return entry;
    }

    function getAchievementMetrics(userId) {
        const volunteerById = new Map(getCollection("voluntariados").map(item => [item.voluntariados_id, item]));
        const attendanceRows = getCollection("asistencias").filter(row => row.usuario_id === userId && row.confirmada);
        const completedVolunteers = attendanceRows.map(row => volunteerById.get(row.voluntariado_id)).filter(Boolean);
        const categoriesCompleted = new Set(completedVolunteers.map(item => normalizeCategory(item.categoria)));
        const locationsCompleted = new Set(completedVolunteers.map(item => item.lugar_id).filter(Boolean));
        const publishedArticles = getCollection("articulos").filter(row => row.autor_id === userId && row.estado === "publicado");
        const points = getCollection("puntos").filter(row => row.usuario_id === userId).reduce((total, row) => total + Number(row.cantidad || 0), 0);
        const minutes = completedVolunteers.reduce((total, volunteer) => {
            const start = /^(\d{2}):(\d{2})$/.exec(volunteer.horarioInicio || "");
            const end = /^(\d{2}):(\d{2})$/.exec(volunteer.horarioFin || "");
            if (!start || !end) return total;
            const startMinutes = Number(start[1]) * 60 + Number(start[2]);
            let endMinutes = Number(end[1]) * 60 + Number(end[2]);
            if (endMinutes < startMinutes) endMinutes += 24 * 60;
            return total + endMinutes - startMinutes;
        }, 0);
        return {
            asistencias: attendanceRows.length,
            voluntariados: completedVolunteers.length,
            articulos: publishedArticles.length,
            actividadesCosta: completedVolunteers.filter(item => coastalCategories.has(normalizeCategory(item.categoria))).length,
            categorias: categoriesCompleted,
            lugares: locationsCompleted,
            horas: minutes / 60,
            puntos: points
        };
    }

    function evaluateAchievement(medal, metrics) {
        let current = 0;
        let target = 1;
        let progressText = "";
        let remaining = "";
        switch (medal.medalla_id) {
            case "medalla-primer-paso":
                current = Math.min(1, metrics.voluntariados);
                progressText = `${current} / 1 voluntariado`;
                remaining = current ? "Logro completado." : "Completa tu primera actividad.";
                break;
            case "medalla-guardian-costa":
                current = metrics.actividadesCosta;
                target = 3;
                progressText = `${Math.min(current, target)} / ${target} actividades de costa`;
                remaining = `${Math.max(0, target - current)} actividad${target - current === 1 ? "" : "es"} de limpieza o conservación.`;
                break;
            case "medalla-guardian-territorio":
                current = metrics.lugares.size;
                target = 3;
                progressText = `${Math.min(current, target)} / ${target} lugares`;
                remaining = `${Math.max(0, target - current)} lugar${target - current === 1 ? "" : "es"} diferente${target - current === 1 ? "" : "s"}.`;
                break;
            case "medalla-voluntario-constante":
                current = metrics.voluntariados;
                target = 5;
                progressText = `${Math.min(current, target)} / ${target} voluntariados`;
                remaining = `Completa ${Math.max(0, target - current)} actividad${target - current === 1 ? "" : "es"} más.`;
                break;
            case "medalla-impacto-local":
                current = metrics.voluntariados;
                target = 10;
                progressText = `${Math.min(current, target)} / ${target} voluntariados`;
                remaining = `Completa ${Math.max(0, target - current)} actividades más.`;
                break;
            case "medalla-voz-ambiental":
                current = metrics.articulos;
                progressText = `${Math.min(current, target)} / 1 artículo`;
                remaining = current ? "Logro completado." : "Publica un artículo aprobado.";
                break;
            case "medalla-comunicador":
                current = metrics.articulos;
                target = 3;
                progressText = `${Math.min(current, target)} / ${target} artículos`;
                remaining = `Publica ${Math.max(0, target - current)} artículo${target - current === 1 ? "" : "s"} aprobado${target - current === 1 ? "" : "s"}.`;
                break;
            case "medalla-educador":
                current = metrics.articulos;
                target = 5;
                progressText = `${Math.min(current, target)} / ${target} artículos`;
                remaining = `Publica ${Math.max(0, target - current)} artículo${target - current === 1 ? "" : "s"} aprobado${target - current === 1 ? "" : "s"}.`;
                break;
            case "medalla-explorador":
                current = metrics.categorias.size;
                target = 3;
                progressText = `${Math.min(current, target)} / ${target} categorías`;
                remaining = `Explora ${Math.max(0, target - current)} categoría${target - current === 1 ? "" : "s"} más.`;
                break;
            case "medalla-regenera":
                current = Math.min(10, metrics.voluntariados, metrics.articulos * 2);
                target = 10;
                progressText = `${metrics.voluntariados} / 10 voluntariados · ${metrics.articulos} / 5 artículos`;
                remaining = `Completa ${Math.max(0, 10 - metrics.voluntariados)} voluntariados y publica ${Math.max(0, 5 - metrics.articulos)} artículos.`;
                break;
            default:
                current = 0;
                progressText = "Criterio personalizado";
                remaining = medal.criterio;
        }
        const awarded = getCollection("usuarioMedallas").some(row => row.usuario_id === metrics.usuario_id && row.medalla_id === medal.medalla_id);
        const completed = medal.medalla_id === "medalla-regenera"
            ? metrics.voluntariados >= 10 && metrics.articulos >= 5
            : current >= target;
        return { ...medal, current: Math.min(current, target), target, ratio: target ? Math.min(1, current / target) : 0, progressText, remaining, unlocked: awarded || completed };
    }

    function getAchievementProgress(userId) {
        const metrics = getAchievementMetrics(userId);
        metrics.usuario_id = userId;
        const earnedIds = new Set(getCollection("usuarioMedallas").filter(row => row.usuario_id === userId).map(row => row.medalla_id));
        return getCollection("medallas").map(medal => ({
            ...evaluateAchievement(medal, metrics),
            unlocked: earnedIds.has(medal.medalla_id),
            unlockedAt: getCollection("usuarioMedallas").find(row => row.usuario_id === userId && row.medalla_id === medal.medalla_id)?.fechaDesbloqueo || null
        }));
    }

    function checkUserAchievements(userId) {
        const earned = getCollection("usuarioMedallas");
        const earnedIds = new Set(earned.filter(row => row.usuario_id === userId).map(row => row.medalla_id));
        const metrics = getAchievementMetrics(userId);
        const unlocked = [];
        getCollection("medallas").forEach(medal => {
            if (earnedIds.has(medal.medalla_id)) return;
            const progress = evaluateAchievement(medal, { ...metrics, usuario_id: userId });
            if (!progress.unlocked) return;
            const relation = {
                usuario_medalla_id: createId("umed"),
                usuario_id: userId,
                medalla_id: medal.medalla_id,
                fechaDesbloqueo: new Date().toISOString()
            };
            earned.push(relation);
            earnedIds.add(medal.medalla_id);
            unlocked.push(medal);
            audit(userId, "MEDALLA_DESBLOQUEADA", "medallas", medal.medalla_id, `Desbloqueó ${medal.nombre}.`);
            addNotification(userId, "medalla", "¡Nueva medalla desbloqueada!", `Obtuviste ${medal.nombre}: ${medal.descripcion}`, { tipo: "medalla", id: medal.medalla_id });
            awardPoints(userId, Number(medal.puntos) || 0, `Medalla ${medal.nombre}`, "medallas", medal.medalla_id);
        });
        if (unlocked.length) saveCollection("usuarioMedallas", earned);
        return unlocked;
    }

    function getUserImpact(userId) {
        const metrics = getAchievementMetrics(userId);
        const enrollments = getCollection("inscripciones").filter(row => row.usuario_id === userId && ["activa", "completada"].includes(row.estado));
        const earned = getCollection("usuarioMedallas").filter(row => row.usuario_id === userId);
        const achievements = getAchievementProgress(userId);
        const nextAchievement = achievements.filter(row => !row.unlocked).sort((a, b) => b.ratio - a.ratio)[0] || null;
        return {
            voluntariados: metrics.voluntariados,
            inscripciones: enrollments.length,
            horas: metrics.horas,
            asistencias: metrics.asistencias,
            articulos: metrics.articulos,
            medallas: earned.length,
            puntos: metrics.puntos,
            nextAchievement,
            achievements
        };
    }

    function synchronizeExistingRewards() {
        const users = getCollection("usuarios");
        const enrollments = getCollection("inscripciones");
        const attendanceRows = getCollection("asistencias").filter(row => row.confirmada);
        users.forEach(user => {
            enrollments.filter(row => row.usuario_id === user.usuario_id && ["activa", "completada"].includes(row.estado))
                .forEach(row => awardPoints(user.usuario_id, 10, "Inscripción a un voluntariado", "inscripciones", row.inscripciones_id, { notify: false, audit: false }));
            const categorySet = new Set();
            attendanceRows.filter(row => row.usuario_id === user.usuario_id).forEach(row => {
                const volunteer = getById("voluntariados", row.voluntariado_id);
                if (!volunteer) return;
                awardPoints(user.usuario_id, 50, "Asistencia confirmada", "asistencias", row.asistencia_id, { notify: false, audit: false });
                const category = normalizeCategory(volunteer.categoria);
                if (!categorySet.has(category)) {
                    awardPoints(user.usuario_id, 10, `Primera actividad de categoría: ${category}`, "categorias", category, { notify: false, audit: false });
                    categorySet.add(category);
                }
                if (category === "conservación") awardPoints(user.usuario_id, 30, "Actividad de conservación completada", "voluntariados", volunteer.voluntariados_id, { notify: false, audit: false });
            });
            getCollection("articulos").filter(row => row.autor_id === user.usuario_id && row.estado === "publicado")
                .forEach(row => awardPoints(user.usuario_id, 25, "Artículo aprobado", "articulos", row.articulos_id, { notify: false, audit: false }));
        });
        users.forEach(user => checkUserAchievements(user.usuario_id));
    }

    function randomAttendanceToken() {
        const bytes = new Uint8Array(4);
        if (window.crypto?.getRandomValues) window.crypto.getRandomValues(bytes);
        else for (let index = 0; index < bytes.length; index += 1) bytes[index] = Math.floor(Math.random() * 256);
        return [...bytes].map(value => value.toString(16).padStart(2, "0")).join("").toUpperCase().slice(0, 6);
    }

    function getAttendanceCode(volunteerId) {
        const codes = getCollection("codigosAsistencia").filter(row => row.voluntariado_id === volunteerId);
        return codes.sort((a, b) => new Date(b.fechaCreacion) - new Date(a.fechaCreacion))[0] || null;
    }

    function renderAchievementCard(achievement) {
        const progress = Math.round(achievement.ratio * 100);
        return `<article class="medal-card ${achievement.unlocked ? "is-unlocked" : "is-locked"}">
            <div class="medal-icon" aria-hidden="true">${achievement.unlocked ? escapeHtml(achievement.icono) : "🔒"}</div>
            <div class="medal-card-content">
                <span class="medal-state">${achievement.unlocked ? "Desbloqueada" : "En progreso"}</span>
                <h3>${escapeHtml(achievement.nombre)}</h3>
                <p>${escapeHtml(achievement.descripcion)}</p>
                <div class="medal-progress" role="progressbar" aria-label="Progreso: ${escapeHtml(achievement.nombre)}" aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100">
                    <span style="width:${progress}%"></span>
                </div>
                <span class="medal-progress-label">${escapeHtml(achievement.unlocked ? achievement.progressText : `${achievement.progressText} · ${achievement.remaining}`)}</span>
            </div>
        </article>`;
    }

    function generateAttendanceCode(volunteerId) {
        const organizer = getCurrentUser();
        const volunteer = getById("voluntariados", volunteerId);
        if (!canManageVolunteer(organizer, volunteer)) throw new Error("Solo quien creó esta actividad o administración puede generar códigos.");
        if (volunteer.estado !== "publicado") throw new Error("Solo se generan códigos para actividades publicadas.");
        const now = new Date();
        const expiration = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
        const codes = getCollection("codigosAsistencia");
        codes.filter(row => row.voluntariado_id === volunteerId && row.activo).forEach(row => { row.activo = false; });
        let code = `REGENERA-${randomAttendanceToken()}`;
        while (codes.some(row => row.codigo === code)) code = `REGENERA-${randomAttendanceToken()}`;
        const entry = {
            codigo_asistencia_id: createId("code"),
            voluntariado_id: volunteerId,
            codigo: code,
            fechaCreacion: now.toISOString(),
            fechaExpiracion: expiration.toISOString(),
            activo: true
        };
        codes.unshift(entry);
        saveCollection("codigosAsistencia", codes);
        audit(organizer.usuario_id, "CODIGO_ASISTENCIA_GENERADO", "voluntariados", volunteerId, `Generó el código ${code}, expira ${expiration.toISOString()}.`);
        return entry;
    }

    function completeAttendance(volunteer, userId, method, actorId) {
        const inscriptions = getCollection("inscripciones");
        const inscription = inscriptions.find(row => row.usuario_id === userId && row.voluntariado_id === volunteer.voluntariados_id && row.estado === "activa");
        if (!inscription) return { ok: false, message: "No estás inscrito en este voluntariado." };
        const now = new Date();
        const attendance = {
            asistencia_id: createId("asis"),
            usuario_id: userId,
            voluntariado_id: volunteer.voluntariados_id,
            fecha: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`,
            hora: now.toTimeString().slice(0, 5),
            metodo: method === "QR" ? "QR" : "codigo",
            confirmada: true
        };
        const assistances = getCollection("asistencias");
        assistances.push(attendance);
        saveCollection("asistencias", assistances);
        inscription.estado = "completada";
        saveCollection("inscripciones", inscriptions);
        addNotification(userId, "asistencia", "¡Asistencia registrada!", `Tu participación en "${volunteer.titulo}" fue confirmada.`, { tipo: "voluntariado", id: volunteer.voluntariados_id });
        audit(actorId || userId, "ASISTENCIA_REGISTRADA", "voluntariados", volunteer.voluntariados_id, `Asistencia registrada para ${userId} mediante ${attendance.metodo}.`);
        awardPoints(userId, 50, "Asistencia confirmada", "asistencias", attendance.asistencia_id);
        const previousAttendances = getCollection("asistencias").filter(row => row.usuario_id === userId && row.confirmada && row.asistencia_id !== attendance.asistencia_id);
        const hadCategory = previousAttendances.some(row => getById("voluntariados", row.voluntariado_id)?.categoria === volunteer.categoria);
        if (!hadCategory) awardPoints(userId, 10, `Primera actividad de categoría: ${volunteer.categoria}`, "categorias", volunteer.categoria);
        if (volunteer.categoria === "conservación") awardPoints(userId, 30, "Actividad de conservación completada", "voluntariados", volunteer.voluntariados_id);
        checkUserAchievements(userId);
        return { ok: true, attendance };
    }

    function registerAttendance(volunteerId, codeValue, method = "codigo") {
        const user = getCurrentUser();
        if (!user) return { ok: false, reason: "login", message: "Inicia sesión para registrar tu asistencia." };
        const volunteer = getById("voluntariados", volunteerId);
        if (!volunteer) return { ok: false, message: "No se encontró este voluntariado." };
        if (!["publicado", "finalizado"].includes(volunteer.estado)) return { ok: false, message: "La asistencia solo se registra en actividades publicadas o finalizadas." };
        if (!getCollection("inscripciones").some(row => row.usuario_id === user.usuario_id && row.voluntariado_id === volunteerId && ["activa", "completada"].includes(row.estado))) {
            return { ok: false, message: "No estás inscrito en este voluntariado." };
        }
        if (getCollection("asistencias").some(row => row.usuario_id === user.usuario_id && row.voluntariado_id === volunteerId && row.confirmada)) {
            return { ok: false, message: "Ya registraste tu asistencia." };
        }
        const value = String(codeValue || "").trim().toUpperCase();
        const code = getCollection("codigosAsistencia").find(row => row.voluntariado_id === volunteerId && row.codigo === value && row.activo);
        if (!code) return { ok: false, message: "El código no es válido." };
        if (code.fechaExpiracion && new Date(code.fechaExpiracion) <= new Date()) return { ok: false, message: "El código de asistencia ha expirado." };
        return completeAttendance(volunteer, user.usuario_id, method, user.usuario_id);
    }

    function normalizeCategory(value) {
        const normalized = String(value || "").trim().toLowerCase();
        return categories.find(category => category.toLowerCase() === normalized) || "conservación";
    }

    function getPlace(volunteer) {
        return volunteer ? getById("lugares", volunteer.lugar_id) : null;
    }

    function hasVolunteerStarted(volunteer) {
        const start = new Date(`${volunteer?.fecha || ""}T${volunteer?.horarioInicio || ""}`);
        return Number.isNaN(start.getTime()) || start <= new Date();
    }

    function getPublicVolunteers() {
        return getCollection("voluntariados").filter(item => item.estado === "publicado" && !hasVolunteerStarted(item)).map(item => {
            const place = getPlace(item);
            const organizer = getById("usuarios", item.organizador_id);
            const participants = getCollection("inscripciones").filter(row => row.voluntariado_id === item.voluntariados_id && ["activa", "completada"].includes(row.estado)).length;
            return { ...item, lugar: place, organizador: organizer, participantes: participants };
        }).sort((left, right) => new Date(`${left.fecha}T${left.horarioInicio}`) - new Date(`${right.fecha}T${right.horarioInicio}`));
    }

    function getPublicArticles() {
        return getCollection("articulos").filter(item => item.estado === "publicado").map(item => ({ ...item, autor: getById("usuarios", item.autor_id) }));
    }

    function getPublicFlora() {
        return getCollection("floraFauna").filter(item => item.estado !== "inactivo");
    }

    function getVolunteerParticipants(volunteerId) {
        return getCollection("inscripciones")
            .filter(row => row.voluntariado_id === volunteerId && ["activa", "completada"].includes(row.estado))
            .map(row => ({ inscription: row, user: getById("usuarios", row.usuario_id) }))
            .filter(row => row.user);
    }

    function joinVolunteer(volunteerId) {
        const user = getCurrentUser();
        if (!user) return { ok: false, reason: "login", message: "Inicia sesión para inscribirte." };
        const volunteer = getById("voluntariados", volunteerId);
        if (!volunteer || volunteer.estado !== "publicado") return { ok: false, message: "Este voluntariado no está disponible." };
        if (hasVolunteerStarted(volunteer)) return { ok: false, reason: "started", message: "La actividad ya comenzó o su fecha no es válida; no admite inscripciones." };
        const inscriptions = getCollection("inscripciones");
        const existing = inscriptions.find(row => row.usuario_id === user.usuario_id && row.voluntariado_id === volunteerId && ["activa", "completada"].includes(row.estado));
        if (existing) return { ok: false, reason: "duplicate", message: "Ya estás inscrito." };
        const participants = inscriptions.filter(row => row.voluntariado_id === volunteerId && ["activa", "completada"].includes(row.estado)).length;
        if (participants >= Number(volunteer.capacidad)) return { ok: false, reason: "full", message: "Cupo lleno." };
        const inscription = { inscripciones_id: createId("ins"), usuario_id: user.usuario_id, voluntariado_id: volunteerId, fechaInscripciones: new Date().toISOString(), estado: "activa" };
        inscriptions.push(inscription);
        saveCollection("inscripciones", inscriptions);
        addNotification(user.usuario_id, "inscripcion", "Inscripción confirmada", `Te inscribiste en "${volunteer.titulo}".`, { tipo: "voluntariado", id: volunteerId });
        audit(user.usuario_id, "USUARIO_INSCRIPCION_VOLUNTARIADO", "voluntariados", volunteerId, `Inscripción ${inscription.inscripciones_id}.`);
        awardPoints(user.usuario_id, 10, "Inscripción a un voluntariado", "inscripciones", inscription.inscripciones_id);
        return { ok: true, inscription };
    }

    function cancelVolunteerEnrollment(volunteerId) {
        const user = getCurrentUser();
        if (!user) return { ok: false, reason: "login", message: "Inicia sesión para cancelar tu inscripción." };
        const volunteer = getById("voluntariados", volunteerId);
        if (!volunteer || volunteer.estado !== "publicado") return { ok: false, message: "Este voluntariado ya no admite cambios de inscripción." };
        const inscriptions = getCollection("inscripciones");
        const inscription = inscriptions.find(row => row.usuario_id === user.usuario_id && row.voluntariado_id === volunteerId && row.estado === "activa");
        if (!inscription) return { ok: false, message: "No tienes una inscripción activa para cancelar." };
        if (getCollection("asistencias").some(row => row.usuario_id === user.usuario_id && row.voluntariado_id === volunteerId && row.confirmada)) {
            return { ok: false, message: "No se puede cancelar una inscripción con asistencia confirmada." };
        }
        inscription.estado = "cancelada";
        inscription.fechaCancelacion = new Date().toISOString();
        saveCollection("inscripciones", inscriptions);
        addNotification(user.usuario_id, "inscripcion", "Inscripción cancelada", `Cancelaste tu lugar en "${volunteer.titulo}".`, { tipo: "voluntariado", id: volunteerId });
        audit(user.usuario_id, "USUARIO_CANCELO_INSCRIPCION", "voluntariados", volunteerId, `Inscripción ${inscription.inscripciones_id} cancelada.`);
        return { ok: true, inscription };
    }

    function markAttendance(volunteerId, userId) {
        const current = getCurrentUser();
        const volunteer = getById("voluntariados", volunteerId);
        if (!canManageVolunteer(current, volunteer)) return { ok: false, message: "Solo quien creó esta actividad o administración puede gestionar asistencia." };
        if (!["publicado", "finalizado"].includes(volunteer.estado)) return { ok: false, message: "La asistencia solo se registra en actividades publicadas o finalizadas." };
        if (getCollection("asistencias").some(row => row.usuario_id === userId && row.voluntariado_id === volunteerId && row.confirmada)) return { ok: false, message: "La asistencia ya está confirmada." };
        const result = completeAttendance(volunteer, userId, "codigo", current.usuario_id);
        if (!result.ok) return { ...result, message: "No hay una inscripción activa para esta persona." };
        return result;
    }

    function visibleDetail(type, id) {
        const user = getCurrentUser();
        const item = type === "articulo"
            ? getById("articulos", id)
            : type === "voluntariado"
                ? getById("voluntariados", id)
                : type === "flora"
                    ? getById("floraFauna", id)
                    : null;
        if (!item) return null;
        if (type === "articulo" && item.estado !== "publicado" && user?.rol !== "administrador" && user?.usuario_id !== item.autor_id) return null;
        if (type === "voluntariado" && item.estado !== "publicado" && user?.rol !== "administrador" && user?.usuario_id !== item.organizador_id) return null;
        if (type === "flora" && item.estado === "inactivo" && user?.rol !== "administrador") return null;
        return item;
    }

    function detailUrl(type, id) {
        return `detalle.html?tipo=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`;
    }

    function escapeHtml(value) {
        return String(value ?? "").replace(/[&<>"']/g, character => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        })[character]);
    }

    function formatDate(value, options = {}) {
        if (!value) return "Fecha por confirmar";
        const date = new Date(value.length === 10 ? `${value}T12:00:00` : value);
        if (Number.isNaN(date.getTime())) return String(value);
        return new Intl.DateTimeFormat("es-MX", { day: "numeric", month: "long", year: options.year === false ? undefined : "numeric" }).format(date);
    }

    function formatTime(value) {
        const match = /^(\d{2}):(\d{2})$/.exec(String(value || ""));
        const date = match
            ? new Date(2000, 0, 1, Number(match[1]), Number(match[2]))
            : new Date(value);
        if (!match && (!value || Number.isNaN(date.getTime()))) return value || "Horario por confirmar";
        return new Intl.DateTimeFormat("es-MX", { hour: "numeric", minute: "2-digit" }).format(date);
    }

    function formatVolunteerDuration(volunteer) {
        const start = /^(\d{2}):(\d{2})$/.exec(String(volunteer?.horarioInicio || ""));
        const end = /^(\d{2}):(\d{2})$/.exec(String(volunteer?.horarioFin || ""));
        if (!start || !end) return "";
        const startMinutes = Number(start[1]) * 60 + Number(start[2]);
        let endMinutes = Number(end[1]) * 60 + Number(end[2]);
        if (endMinutes < startMinutes) endMinutes += 24 * 60;
        const duration = endMinutes - startMinutes;
        if (duration <= 0) return "";
        const hours = Math.floor(duration / 60);
        const minutes = duration % 60;
        return [hours ? `${hours} h` : "", minutes ? `${minutes} min` : ""].filter(Boolean).join(" ");
    }

    function statusBadge(status) {
        const safe = escapeHtml(status);
        return `<span class="status-badge status-${safe}">${safe}</span>`;
    }

    function renderNavigation() {
        const user = getCurrentUser();
        document.querySelectorAll(".user-nav").forEach(link => {
            link.href = user ? "perfil.html" : "login.html";
            link.textContent = user ? "Mi perfil" : "Iniciar sesión";
        });
        document.querySelectorAll(".nav, .mobile-menu").forEach(nav => {
            const addLink = (key, text, href, condition = true) => {
                let link = nav.querySelector(`[data-session-link="${key}"]`);
                if (!condition) {
                    link?.remove();
                    return;
                }
                if (!link) {
                    link = document.createElement("a");
                    link.dataset.sessionLink = key;
                    nav.appendChild(link);
                }
                link.href = href;
                link.textContent = text;
            };
            let profileLink = nav.querySelector(".user-nav, [data-session-link=profile], a[href='perfil.html']");
            if (user) {
                if (!profileLink) {
                    addLink("profile", "Mi perfil", "perfil.html");
                    profileLink = nav.querySelector('[data-session-link="profile"]');
                }
                profileLink.href = "perfil.html";
                profileLink.textContent = "Mi perfil";
                profileLink.dataset.sessionLink = "profile";
            } else {
                addLink("profile", "", "", false);
                if (profileLink) {
                    profileLink.href = "login.html";
                    profileLink.textContent = "Iniciar sesión";
                    profileLink.classList.add("user-nav");
                    delete profileLink.dataset.sessionLink;
                } else {
                    addLink("auth", "Iniciar sesión", "login.html");
                }
            }
            addLink("create", "", "", false);
            addLink("medals", "", "", false);
            addLink("admin", "Dashboard", "admin.html", user?.rol === "administrador");
            addLink("organizer", "Mis voluntariados", "perfil.html#organizerSection", user && (user.rol === "organizador" || user.rol === "administrador"));
            nav.querySelectorAll('a[href="#reporte"], a[href="index.html#reporte"]').forEach(link => link.remove());
            if (!user) {
                nav.querySelectorAll("[data-session-action=logout], .notification-trigger, .notification-panel").forEach(item => item.remove());
                return;
            }

            if (!nav.querySelector(`[data-session-action="logout"]`)) {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "session-logout";
                button.dataset.sessionAction = "logout";
                button.textContent = "Cerrar sesión";
                button.addEventListener("click", logout);
                nav.appendChild(button);
            }
        });
        const primaryNav = document.querySelector(".topbar .nav");
        const header = primaryNav?.closest(".topbar");
        if (primaryNav && header) {
            let menuButton = header.querySelector(".menu-button");
            if (!menuButton) {
                menuButton = document.createElement("button");
                menuButton.className = "menu-button";
                menuButton.type = "button";
                menuButton.textContent = "☰";
                header.append(menuButton);
            }
            menuButton.setAttribute("aria-label", menuButton.getAttribute("aria-label") || "Abrir menú");
            menuButton.setAttribute("aria-expanded", "false");
            let mobileMenu = header.parentElement.querySelector(".mobile-menu");
            if (!mobileMenu) {
                mobileMenu = document.createElement("nav");
                mobileMenu.className = "mobile-menu";
                mobileMenu.setAttribute("aria-label", "Navegación móvil");
                header.insertAdjacentElement("afterend", mobileMenu);
            }
            mobileMenu.innerHTML = "";
            primaryNav.querySelectorAll("a").forEach(link => {
                const mobileLink = link.cloneNode(true);
                mobileLink.addEventListener("click", () => {
                    mobileMenu.classList.remove("active");
                    menuButton.setAttribute("aria-expanded", "false");
                });
                mobileMenu.append(mobileLink);
            });
            const user = getCurrentUser();
            if (user) {
                const logoutButton = document.createElement("button");
                logoutButton.type = "button";
                logoutButton.className = "session-logout";
                logoutButton.textContent = "Cerrar sesión";
                logoutButton.addEventListener("click", logout);
                mobileMenu.append(logoutButton);
            }
            const existingHomeController = menuButton.id === "menuButton" && mobileMenu.id === "mobileMenu";
            if (!existingHomeController && !menuButton.dataset.menuBound) {
                menuButton.addEventListener("click", () => {
                    const opening = !mobileMenu.classList.contains("active");
                    mobileMenu.classList.toggle("active", opening);
                    menuButton.setAttribute("aria-expanded", String(opening));
                    menuButton.setAttribute("aria-label", opening ? "Cerrar menú" : "Abrir menú");
                });
                menuButton.dataset.menuBound = "true";
            }
        }
        renderNotificationBadge();
    }

    function getUnreadNotifications(userId) {
        return getCollection("notificaciones").filter(item => item.usuario_id === userId && !item.leido);
    }

    function renderNotificationBadge() {
        const user = getCurrentUser();
        document.querySelectorAll(".notification-trigger").forEach(trigger => {
            trigger.textContent = `♧${user && getUnreadNotifications(user.usuario_id).length ? ` ${getUnreadNotifications(user.usuario_id).length}` : ""}`;
            trigger.setAttribute("aria-label", "Mostrar notificaciones");
        });
    }

    function initializeNotificationUi() {
        const user = getCurrentUser();
        document.querySelectorAll(".nav, .mobile-menu").forEach(nav => {
            if (!user || nav.querySelector(".notification-trigger")) return;
            const button = document.createElement("button");
            button.className = "notification-trigger";
            button.type = "button";
            button.setAttribute("aria-expanded", "false");
            const panel = document.createElement("div");
            panel.className = "notification-panel";
            panel.hidden = true;
            button.addEventListener("click", () => {
                const opening = panel.hidden;
                panel.hidden = !opening;
                button.setAttribute("aria-expanded", String(opening));
                if (opening) renderNotifications(panel, user.usuario_id);
            });
            nav.append(button, panel);
        });
        renderNotificationBadge();
    }

    function renderNotifications(panel, userId) {
        const notifications = getCollection("notificaciones").filter(row => row.usuario_id === userId);
        if (!notifications.length) {
            panel.innerHTML = '<p class="notification-empty">No tienes notificaciones.</p>';
            return;
        }
        panel.innerHTML = `<h3>Notificaciones</h3>${notifications.slice(0, 12).map(row => `
            <button class="notification-item ${row.leido ? "is-read" : "is-unread"}" type="button" data-notification-id="${escapeHtml(row.notificaciones_id)}">
                <span>${statusBadge(row.prioridad)}</span>
                <strong>${escapeHtml(row.titulo)}</strong>
                <span>${escapeHtml(row.mensaje)}</span>
                <time>${escapeHtml(formatDate(row.fechaCreacion))}</time>
            </button>`).join("")}`;
        panel.querySelectorAll("[data-notification-id]").forEach(button => {
            button.addEventListener("click", () => {
                const rows = getCollection("notificaciones");
                const notification = rows.find(row => row.notificaciones_id === button.dataset.notificationId);
                if (notification && !notification.leido) {
                    notification.leido = true;
                    notification.fechaLectura = new Date().toISOString();
                    saveCollection("notificaciones", rows);
                }
                const entity = notification?.entidad;
                if (entity?.tipo && entity.id) {
                    window.location.href = detailUrl(entity.tipo === "voluntariado" ? "voluntariado" : entity.tipo === "articulos" ? "articulo" : entity.tipo, entity.id);
                } else {
                    renderNotifications(panel, userId);
                    renderNotificationBadge();
                }
            });
        });
        renderNotificationBadge();
    }

    function renderHomeListings() {
        const volunteerContainer = document.querySelector('[data-home-listing="voluntariado"]');
        if (volunteerContainer) {
            const featured = getPublicVolunteers().slice(0, 3);
            volunteerContainer.innerHTML = featured.length ? featured.map(volunteer => {
                const place = volunteer.lugar;
                const detail = detailUrl("voluntariado", volunteer.voluntariados_id);
                const current = volunteer.participantes;
                return `<article class="volunteer-card">
                    <div class="volunteer-image" style="background-image:url('${escapeHtml(volunteer.imagen)}')">
                        <span class="volunteer-date">${escapeHtml(formatDate(volunteer.fecha, { year: false }))}</span>
                    </div>
                    <div class="volunteer-card-content">
                        <span class="volunteer-category">${escapeHtml(volunteer.categoria)}</span>
                        <h3>${escapeHtml(volunteer.titulo)}</h3>
                        <div class="volunteer-meta">
                            <span>📅 ${escapeHtml(formatDate(volunteer.fecha))}</span>
                            <span>🕐 ${escapeHtml(formatTime(volunteer.horarioInicio))}–${escapeHtml(formatTime(volunteer.horarioFin))}${formatVolunteerDuration(volunteer) ? ` · ${escapeHtml(formatVolunteerDuration(volunteer))}` : ""}</span>
                            <span>📍 ${escapeHtml(place?.nombre || "Lugar por confirmar")}</span>
                            <span>👥 ${current}/${volunteer.capacidad} participantes</span>
                        </div>
                        <p>${escapeHtml(volunteer.descripcion)}</p>
                        ${statusBadge(volunteer.estado)}
                        <span class="volunteer-points">✦ +50 puntos al completar</span>
                        <a class="volunteer-link" href="${detail}">Ver voluntariado <span>→</span></a>
                    </div>
                </article>`;
            }).join("") : '<p class="empty-state">Aún no hay voluntariados publicados.</p>';
            volunteerContainer.querySelectorAll(".volunteer-card").forEach(card => {
                const anchor = card.querySelector(".volunteer-link");
                card.addEventListener("click", event => {
                    if (!event.target.closest("a, button")) window.location.href = anchor.href;
                });
            });
        }

        const articleContainer = document.querySelector('[data-home-listing="articulo"]');
        if (articleContainer) {
            const articles = getPublicArticles().slice(0, 3);
            articleContainer.innerHTML = articles.length ? articles.map(article => `
                <article class="article-card">
                    <div class="article-image" style="background-image:url('${escapeHtml(article.imagen)}')"></div>
                    <div class="article-content">
                        <span>${escapeHtml(article.categoria)}</span>
                        <h3>${escapeHtml(article.titulo)}</h3>
                        <p>${escapeHtml(article.contenido.slice(0, 170))}${article.contenido.length > 170 ? "…" : ""}</p>
                        <p class="article-meta">${escapeHtml(article.autor?.nombre || "Autor por confirmar")} · ${escapeHtml(formatDate(article.fechaPublicacion))}</p>
                        <a href="${detailUrl("articulo", article.articulos_id)}">Leer artículo →</a>
                    </div>
                </article>`).join("") : '<p class="empty-state">Aún no hay artículos publicados.</p>';
        }
    }

    function renderArticleListing() {
        const container = document.querySelector('[data-content-listing="articulo"]');
        if (!container) return;
        const articles = getPublicArticles();
        const createTile = `<a class="article-create-tile" href="crear-contenido.html?tipo=articulo" aria-label="Crear un artículo">
            <span aria-hidden="true">+</span><strong>Crear artículo</strong><small>Comparte una iniciativa o conocimiento ambiental</small>
        </a>`;
        const cards = articles.map(article => `
            <article class="article-page-card">
                <div class="article-page-image" style="background-image:url('${escapeHtml(article.imagen)}')"></div>
                <div class="article-page-content">
                    <span>${escapeHtml(article.categoria)}</span>
                    <h2>${escapeHtml(article.titulo)}</h2>
                    <p>${escapeHtml(article.contenido.slice(0, 220))}${article.contenido.length > 220 ? "…" : ""}</p>
                    <p class="article-meta">${escapeHtml(article.autor?.nombre || "Autor por confirmar")} · ${escapeHtml(formatDate(article.fechaPublicacion))}</p>
                    <a class="article-link" href="${detailUrl("articulo", article.articulos_id)}">Leer artículo →</a>
                </div>
            </article>`).join("");
        container.innerHTML = `${createTile}${cards || '<p class="empty-state">Aún no hay artículos publicados.</p>'}`;
    }

    function renderFloraListing() {
        const container = document.querySelector('[data-content-listing="flora"]');
        if (!container) return;
        const filter = document.getElementById("floraFilter")?.value || "todos";
        const query = String(document.getElementById("floraSearch")?.value || "").trim().toLocaleLowerCase("es-MX");
        const entries = getPublicFlora().filter(item => {
            const matchesType = filter === "todos" || item.tipo === filter;
            const searchable = [item.nombre, item.nombreCientifico, item.habitat].join(" ").toLocaleLowerCase("es-MX");
            return matchesType && (!query || searchable.includes(query));
        });
        container.innerHTML = entries.map(item => `
            <article class="info-card">
                <div class="info-card-image" style="background-image:url('${escapeHtml(item.imagenes?.[0] || "")}')"></div>
                <div class="info-card-content">
                    <span class="info-card-category">${escapeHtml(item.tipo)}</span>
                    <h3>${escapeHtml(item.nombre)}</h3>
                    <p class="flora-scientific-name">${escapeHtml(item.nombreCientifico || "Nombre científico no registrado en la demo")}</p>
                    <p>${escapeHtml(item.descripcion)}</p>
                    <p class="flora-card-meta"><strong>Hábitat:</strong> ${escapeHtml(item.habitat || "Información no disponible en la demo")}</p>
                    <a class="info-card-link" href="${detailUrl("flora", item.floraFauna_id)}">Explorar ficha →</a>
                </div>
            </article>`).join("") || '<p class="empty-state">No hay especies en este filtro.</p>';
    }

    function renderDetailPage() {
        const target = document.getElementById("detailContent");
        if (!target) return;
        const params = new URLSearchParams(window.location.search);
        const type = params.get("tipo");
        const id = params.get("id");
        const item = visibleDetail(type, id);
        if (!item) {
            target.innerHTML = '<section class="detail-not-found"><h1>Contenido no disponible</h1><p>El elemento no existe, no está publicado o no tienes permiso para verlo.</p><a href="index.html">Volver al inicio</a></section>';
            return;
        }

        const isVolunteer = type === "voluntariado";
        const isFlora = type === "flora";
        const isArticle = type === "articulo";
        const title = item.titulo || item.nombre;
        const image = isFlora ? item.imagenes?.[0] : item.imagen;
        const detailCategory = isFlora ? item.nombreCientifico || "Ficha ambiental" : item.categoria || "";
        const user = getCurrentUser();
        const author = isVolunteer ? getById("usuarios", item.organizador_id) : !isFlora ? getById("usuarios", item.autor_id) : null;
        const place = isVolunteer ? getPlace(item) : null;
        const inscriptions = isVolunteer ? getCollection("inscripciones").filter(row => row.voluntariado_id === id && ["activa", "completada"].includes(row.estado)) : [];
        const mine = isVolunteer && user ? inscriptions.some(row => row.usuario_id === user.usuario_id && ["activa", "completada"].includes(row.estado)) : false;
        const myActiveEnrollment = isVolunteer && user ? inscriptions.find(row => row.usuario_id === user.usuario_id && row.estado === "activa") : null;
        const count = inscriptions.length;
        const full = Number(item.capacidad) > 0 && count >= Number(item.capacidad);
        const hasAttendance = Boolean(isVolunteer && user && getCollection("asistencias").some(row => row.usuario_id === user.usuario_id && row.voluntariado_id === id && row.confirmada));
        const isOwner = Boolean(user && isVolunteer && item.organizador_id === user.usuario_id);
        const admin = user?.rol === "administrador";
        const manages = isVolunteer && ["publicado", "finalizado"].includes(item.estado) && canManageVolunteer(user, item);
        const attendanceCode = manages && isVolunteer ? getAttendanceCode(id) : null;
        const codeExpired = attendanceCode?.fechaExpiracion && new Date(attendanceCode.fechaExpiracion) <= new Date();
        const returnPage = { articulo: "articulos.html", flora: "flora.html", voluntariado: "voluntariados.html" }[type] || "index.html";
        const details = isVolunteer ? `
            <div class="detail-meta">
                <span>📅 ${escapeHtml(formatDate(item.fecha))}</span>
                <span>🕐 ${escapeHtml(formatTime(item.horarioInicio))}–${escapeHtml(formatTime(item.horarioFin))}${formatVolunteerDuration(item) ? ` · ${escapeHtml(formatVolunteerDuration(item))}` : ""}</span>
                <span>📍 ${escapeHtml(place?.nombre || "Lugar no disponible")}</span>
                <span>👥 ${count}/${escapeHtml(item.capacidad)} participantes</span>
            </div>
            <dl class="detail-facts">
                <div><dt>Dirección</dt><dd>${escapeHtml(place?.ubicacion?.direccion || item.ubicacion)}</dd></div>
                <div><dt>Punto de encuentro</dt><dd>${escapeHtml(item.ubicacion)}</dd></div>
                <div><dt>Organiza</dt><dd>${escapeHtml(author?.nombre || "Organizador")}</dd></div>
                <div><dt>Requisitos</dt><dd>${escapeHtml(item.requisitos || "No especificados")}</dd></div>
                <div><dt>Materiales</dt><dd>${escapeHtml(item.materiales || "No especificados")}</dd></div>
                ${place?.ubicacion?.latitud != null ? `<div><dt>Coordenadas</dt><dd>${escapeHtml(place.ubicacion.latitud)}, ${escapeHtml(place.ubicacion.longitud)}</dd></div>` : ""}
            </dl>` : isFlora ? `
            <div class="flora-detail-stages">
                <section class="flora-detail-stage">
                    <span>ETAPA 1 · IDENTIFICACIÓN</span><h2>Identificación</h2>
                    <dl class="detail-facts">
                        <div><dt>Nombre común</dt><dd>${escapeHtml(item.nombre || "No disponible en la demo")}</dd></div>
                        <div><dt>Nombre científico</dt><dd>${escapeHtml(item.nombreCientifico || "No registrado en la demo")}</dd></div>
                        <div><dt>Tipo</dt><dd>${escapeHtml(item.tipo || "No clasificado")}</dd></div>
                        <div><dt>Conservación</dt><dd>${escapeHtml(item.estadoConservacion ? `Dato de demostración: ${item.estadoConservacion}. Fuente no registrada.` : "No disponible en la demo")}</dd></div>
                    </dl>
                </section>
                <section class="flora-detail-stage">
                    <span>ETAPA 2 · DESCRIPCIÓN</span><h2>Descripción y hábitat</h2>
                    <div class="detail-copy"><p>${escapeHtml(item.descripcion || "Descripción no disponible en la demo.")}</p></div>
                    <dl class="detail-facts">
                        <div><dt>Hábitat</dt><dd>${escapeHtml(item.habitat || "No disponible en la demo")}</dd></div>
                        <div><dt>Función ecológica</dt><dd>${escapeHtml(item.funcionEcologica || "No documentada en los datos de la demo")}</dd></div>
                        <div><dt>Dato curioso</dt><dd>${escapeHtml(item.datoCurioso || "No documentado en los datos de la demo")}</dd></div>
                    </dl>
                </section>
                <section class="flora-detail-stage">
                    <span>ETAPA 3 · DISTRIBUCIÓN</span><h2>Ubicación en Baja California Sur</h2>
                    <p>${escapeHtml(item.ubicacion || "Distribución no registrada en la demo")}</p>
                    <p class="detail-data-note">La ubicación es orientativa. Esta demo no incluye mapa ni datos de observación verificados.</p>
                </section>
                <section class="flora-detail-stage">
                    <span>ETAPA 4 · CONSERVACIÓN</span><h2>Conservación</h2>
                    <dl class="detail-facts">
                        <div><dt>Amenazas documentadas</dt><dd>${escapeHtml(item.amenazas || "No documentadas en la demo")}</dd></div>
                        <div><dt>Recomendaciones específicas</dt><dd>${escapeHtml(item.recomendaciones || "No documentadas en la demo")}</dd></div>
                        <div><dt>Fuentes</dt><dd>${escapeHtml(Array.isArray(item.fuentes) && item.fuentes.length ? item.fuentes.join(" · ") : "No registradas en la demo")}</dd></div>
                    </dl>
                    <p class="detail-data-note">Como pauta general, observa sin manipular organismos y respeta las indicaciones del área que visitas.</p>
                </section>
            </div>` : `
            <div class="detail-meta"><span>${escapeHtml(item.categoria || "Artículo ambiental")}</span><span>${escapeHtml(author?.nombre || "Autor por confirmar")}</span><span>${escapeHtml(formatDate(item.fechaPublicacion || item.fechaActualizacion))}</span></div>`;
        const canJoin = isVolunteer && item.estado === "publicado" && !hasVolunteerStarted(item);
        const registrationClosed = isVolunteer && item.estado === "publicado" && hasVolunteerStarted(item) && !mine;
        const participates = isVolunteer && item.estado === "publicado" && !hasAttendance && canJoin ? `<div class="participation-actions">
            <button class="detail-action" id="participateButton" type="button" ${mine || full ? "disabled" : ""}>${mine ? "Ya estás inscrito" : full ? "Cupo lleno" : "Inscribirme"}</button>
            ${myActiveEnrollment ? '<button class="detail-action secondary-button" id="cancelEnrollmentButton" type="button">Cancelar inscripción</button>' : ""}
            <p class="form-message ${mine ? "is-success" : full ? "is-error" : ""}" id="participationMessage" role="status" aria-live="polite">${mine ? "Ya tienes una inscripción para este voluntariado." : full ? "Cupo lleno." : ""}</p>
        </div>` : registrationClosed ? '<p class="form-message is-error">La inscripción está cerrada: la actividad ya comenzó o su fecha no es válida.</p>' : "";
        const userAttendance = isVolunteer && user && mine && !hasAttendance
            ? `<form class="attendance-code-form" id="attendanceCodeForm" data-volunteer-id="${escapeHtml(id)}">
                <label for="attendanceCodeInput">Código de asistencia</label>
                <p>Introduce el código que te proporcione el organizador. El escaneo QR no está disponible en esta demo.</p>
                <div><input id="attendanceCodeInput" name="codigo" autocomplete="one-time-code" placeholder="Código proporcionado por el organizador" required><button type="submit">Confirmar asistencia</button></div>
                <p class="form-message" id="attendanceCodeMessage" role="status" aria-live="polite"></p>
            </form>`
            : "";
        const management = manages ? `<section class="participant-panel"><h2>Personas inscritas (${inscriptions.length})</h2>
            <div class="attendance-control">
                <div><span class="detail-label">CONTROL DE ASISTENCIA</span><h3>${escapeHtml(item.titulo)}</h3><p>${count} / ${escapeHtml(item.capacidad)} participantes · ${escapeHtml(formatDate(item.fecha))}</p></div>
                <p>El código de demostración vence automáticamente siete días después de generarse.</p>
                <div class="attendance-code-actions"><button type="button" class="attendance-button" id="generateAttendanceCode">Generar nuevo código</button></div>
                ${attendanceCode ? `<div class="attendance-code-display"><span>CÓDIGO ACTIVO${codeExpired ? " · EXPIRADO" : ""}</span><strong>${escapeHtml(attendanceCode.codigo)}</strong><small>Expira ${escapeHtml(formatDate(attendanceCode.fechaExpiracion))} ${escapeHtml(formatTime(attendanceCode.fechaExpiracion))}</small>
                    <button type="button" class="attendance-button secondary-button" id="showAttendanceQr" ${codeExpired ? "disabled" : ""}>Mostrar código QR</button></div>` : '<p>Aún no hay un código generado para esta actividad.</p>'}
                <p class="form-message" id="attendanceControlMessage" role="status" aria-live="polite"></p>
            </div>
            <h3 class="participant-list-title">Participantes inscritos</h3>
            ${inscriptions.map(enrollment => {
                const participant = getById("usuarios", enrollment.usuario_id);
                const confirmed = getCollection("asistencias").some(row => row.usuario_id === enrollment.usuario_id && row.voluntariado_id === id && row.confirmada);
                return `<div class="participant-row"><span>${escapeHtml(participant?.nombre || "Usuario")} · ${escapeHtml(participant?.correo || "")}</span><span>${statusBadge(confirmed ? "completada" : "pendiente")}</span>${enrollment.estado === "activa" ? `<button type="button" class="attendance-button" data-attendance-user="${escapeHtml(enrollment.usuario_id)}" ${confirmed ? "disabled" : ""}>${confirmed ? "Asistencia confirmada" : "Registrar asistencia"}</button>` : ""}</div>`;
            }).join("") || "<p>Aún no hay inscripciones.</p>"}</section>` : "";
        const volunteerManagement = isVolunteer && (admin || isOwner) && !["cancelado", "finalizado"].includes(item.estado)
            ? `<div class="detail-management-actions"><a class="detail-action" href="crear-contenido.html?tipo=voluntariado&id=${encodeURIComponent(id)}">Editar voluntariado</a></div>`
            : "";
        const floraManagement = isFlora && admin ? `<div class="detail-management-actions">
            <a class="detail-action" href="admin.html?tab=flora&editSpecies=${encodeURIComponent(id)}">Editar ficha en administración</a>
            <button type="button" class="detail-action secondary-button" id="toggleSpeciesState">${item.estado === "inactivo" ? "Reactivar ficha" : "Desactivar ficha"}</button>
        </div>` : "";
        const articleManagement = isArticle && (admin || user?.usuario_id === item.autor_id) ? `<div class="detail-management-actions">
            <a class="detail-action" href="crear-contenido.html?tipo=articulo&id=${encodeURIComponent(id)}">Editar artículo</a>
            ${!admin ? "" : `
            <button type="button" class="detail-action" data-article-action="${item.estado === "publicado" || item.estado === "retirado" ? "publicado" : "pendiente"}">${item.estado === "publicado" || item.estado === "retirado" ? "Publicar artículo" : "Devolver a revisión"}</button>
            ${item.estado === "publicado" ? '<button type="button" class="detail-action secondary-button" data-article-action="retirado">Retirar de publicación</button>' : ""}
            ${item.estado === "pendiente" ? '<button type="button" class="detail-action secondary-button" data-article-action="rechazado">Rechazar</button>' : ""}
            <button type="button" class="detail-action danger-button" data-article-action="eliminar">Eliminar artículo</button>`}
        </div>` : "";
        const relatedArticles = isArticle ? getPublicArticles().filter(article => article.articulos_id !== id && article.categoria === item.categoria).slice(0, 3) : [];

        document.title = `${title} · Regenera BCS`;
        target.innerHTML = `<article class="detail-article">
            <img class="detail-image" src="${escapeHtml(image || "")}" alt="${escapeHtml(title)}">
            <div class="detail-body">
                <span class="detail-label">${escapeHtml(isVolunteer ? "Voluntariado" : isFlora ? item.tipo : "Artículo")} · ${escapeHtml(detailCategory)}</span>
                <div class="detail-title-line"><h1>${escapeHtml(title)}</h1>${statusBadge(isFlora ? "Dato demo" : item.estado || "")}</div>
                ${details}
                ${!isFlora ? `<div class="detail-copy">${(isVolunteer ? item.descripcion : item.contenido || item.descripcion).split(/\n+/).filter(Boolean).map(paragraph => `<p>${escapeHtml(paragraph)}</p>`).join("")}</div>` : ""}
                ${isArticle && item.referencias?.length ? `<section class="article-references"><h2>Fuentes y referencias</h2><ul>${item.referencias.map(reference => `<li>${escapeHtml(reference)}</li>`).join("")}</ul></section>` : ""}
                ${relatedArticles.length ? `<section class="related-articles"><h2>Artículos relacionados</h2><div>${relatedArticles.map(article => `<a href="${detailUrl("articulo", article.articulos_id)}">${escapeHtml(article.titulo)} →</a>`).join("")}</div></section>` : ""}
                ${hasAttendance
                    ? '<p class="attendance-confirmed">✓ Asistencia registrada · Voluntariado completado</p>'
                    : isVolunteer && user && mine ? '<p class="impact-reward-note">Al completar esta actividad recibirás <strong>+50 puntos</strong> y podrás avanzar en tus medallas.</p>' : ""}
                ${author ? `<p class="detail-author">${isVolunteer ? "Organiza" : "Autor"}: ${escapeHtml(author.nombre)}</p>` : ""}
                ${participates}${userAttendance}${management}${volunteerManagement}${floraManagement}${articleManagement}
                <a class="section-link" href="${returnPage}">← Volver</a>
            </div>
        </article>`;

        const generateCodeButton = document.getElementById("generateAttendanceCode");
        generateCodeButton?.addEventListener("click", () => {
            const message = document.getElementById("attendanceControlMessage");
            try {
                generateAttendanceCode(id);
                renderDetailPage();
            } catch (error) {
                message.textContent = error.message;
                message.className = "form-message is-error";
            }
        });
        document.getElementById("showAttendanceQr")?.addEventListener("click", () => {
            const payload = JSON.stringify({ voluntariado_id: id, codigoAsistencia: attendanceCode.codigo });
            const dialog = document.createElement("dialog");
            dialog.className = "qr-dialog";
            dialog.innerHTML = `<form method="dialog"><button class="qr-dialog-close" aria-label="Cerrar">×</button></form><span class="detail-label">CÓDIGO QR DE ASISTENCIA</span><h2>${escapeHtml(item.titulo)}</h2><div class="qr-code-image">${window.RegeneraQr.toSvg(payload)}</div><p>El código QR contiene únicamente el ID de la actividad y su código de asistencia.</p><strong>${escapeHtml(attendanceCode.codigo)}</strong>`;
            document.body.append(dialog);
            dialog.addEventListener("close", () => dialog.remove(), { once: true });
            dialog.showModal();
        });
        document.getElementById("attendanceCodeForm")?.addEventListener("submit", event => {
            event.preventDefault();
            const result = registerAttendance(id, new FormData(event.currentTarget).get("codigo"));
            const message = document.getElementById("attendanceCodeMessage");
            message.textContent = result.message || (result.ok ? "¡Asistencia registrada!" : "No se pudo registrar la asistencia.");
            message.className = `form-message ${result.ok ? "is-success" : "is-error"}`;
            if (result.ok) renderDetailPage();
        });
        document.getElementById("participateButton")?.addEventListener("click", () => {
            const result = joinVolunteer(id);
            const message = document.getElementById("participationMessage");
            if (result.reason === "login") {
                window.location.href = `login.html?returnTo=${encodeURIComponent(`detalle.html?tipo=voluntariado&id=${id}`)}`;
                return;
            }
            message.textContent = result.message || (result.ok ? "Tu inscripción quedó registrada." : "No se pudo completar la inscripción.");
            message.className = `form-message ${result.ok ? "is-success" : "is-error"}`;
            document.getElementById("participateButton").textContent = result.ok || result.reason === "duplicate" ? "Ya estás inscrito" : "Inscribirme";
            document.getElementById("participateButton").disabled = result.ok || result.reason === "duplicate" || result.reason === "full";
            if (result.ok) renderDetailPage();
        });
        document.getElementById("cancelEnrollmentButton")?.addEventListener("click", () => {
            if (!window.confirm("¿Cancelar tu inscripción? El registro se conservará en tu historial.")) return;
            const result = cancelVolunteerEnrollment(id);
            const message = document.getElementById("participationMessage");
            message.textContent = result.message || (result.ok ? "Tu inscripción fue cancelada." : "No se pudo cancelar la inscripción.");
            message.className = `form-message ${result.ok ? "is-success" : "is-error"}`;
            if (result.ok) renderDetailPage();
        });
        target.querySelectorAll("[data-attendance-user]").forEach(button => {
            button.addEventListener("click", () => {
                const result = markAttendance(id, button.dataset.attendanceUser);
                const message = document.getElementById("participationMessage");
                if (!result.ok && message) {
                    message.textContent = result.message;
                    message.className = "form-message is-error";
                } else {
                    renderDetailPage();
                }
            });
        });
        document.getElementById("toggleSpeciesState")?.addEventListener("click", () => {
            const nextState = item.estado === "inactivo" ? "activo" : "inactivo";
            if (!window.confirm(`${nextState === "inactivo" ? "Desactivar" : "Reactivar"} esta ficha? El registro se conservará.`)) return;
            updateFlora(id, { estado: nextState });
            renderDetailPage();
        });
        target.querySelectorAll("[data-article-action]").forEach(button => {
            button.addEventListener("click", () => {
                const action = button.dataset.articleAction;
                const actionNames = { publicado: "publicar", pendiente: "devolver a revisión", rechazado: "rechazar", retirado: "retirar de publicación", eliminar: "eliminar" };
                if (!window.confirm(`¿${actionNames[action]} este artículo?`)) return;
                if (action === "eliminar") removeContent("articulos", id);
                else moderateContent("articulos", id, action);
                renderDetailPage();
                renderArticleListing();
            });
        });
        target.querySelectorAll(".detail-management-actions button").forEach(button => {
            button.dataset.tooltip = button.textContent.trim();
            button.setAttribute("aria-description", button.dataset.tooltip);
        });
    }

    function initializeCreateForm() {
        const form = document.getElementById("createContentForm");
        if (!form) return;
        const params = new URLSearchParams(window.location.search);
        const type = params.get("tipo");
        const recordId = params.get("id");
        const user = getCurrentUser();
        const message = document.getElementById("createMessage");
        const showError = text => {
            message.textContent = text;
            message.className = "form-message is-error";
        };
        if (!user) {
            window.location.replace(`login.html?returnTo=${encodeURIComponent(`crear-contenido.html?tipo=${type || ""}${recordId ? `&id=${recordId}` : ""}`)}`);
            return;
        }
        if (!["articulo", "voluntariado"].includes(type)) {
            showError("Elige si quieres crear un artículo o un voluntariado.");
            form.hidden = true;
            return;
        }
        const record = recordId
            ? type === "articulo" ? getById("articulos", recordId) : getById("voluntariados", recordId)
            : null;
        if (recordId && (!record || user.rol !== "administrador" && (type === "articulo" ? record.autor_id : record.organizador_id) !== user.usuario_id)) {
            showError("No se encontró el contenido o no tienes permiso para editarlo.");
            form.hidden = true;
            return;
        }
        if (type === "voluntariado" && user.rol === "organizador" && !user.organizadorVerificado) {
            showError("Tu cuenta de organizador está pendiente de verificación. Puedes crear artículos mientras tanto.");
            form.hidden = true;
            return;
        }
        const article = type === "articulo";
        const publicationField = document.getElementById("adminPublicationField");
        publicationField.hidden = user.rol !== "administrador";
        document.getElementById("createTitle").textContent = `${record ? "Editar" : "Crear"} ${article ? "artículo" : "voluntariado"}`;
        document.getElementById("createSubtitle").textContent = record
            ? user.rol === "administrador" ? "Guarda los cambios y elige si se publica o continúa en revisión." : "Los cambios de contenido publicado se enviarán nuevamente a revisión."
            : article ? "Tu artículo quedará pendiente hasta que el equipo lo revise." : "Tu voluntariado quedará pendiente de aprobación antes de aparecer públicamente.";
        document.querySelectorAll("[data-field-group]").forEach(group => {
            group.hidden = group.dataset.fieldGroup !== type;
            group.querySelectorAll("input, textarea, select").forEach(input => input.required = !group.hidden && input.name !== "requirements" && input.name !== "materials");
        });
        const category = document.getElementById("category");
        category.innerHTML = article
            ? '<option value="">Selecciona una categoría</option><option>comunidad</option><option>naturaleza</option><option>conservación</option><option>turismo</option><option>educación ambiental</option>'
            : `<option value="">Selecciona una categoría</option>${categories.map(value => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`).join("")}`;
        if (!article) {
            const placeSelect = document.getElementById("placeId");
            placeSelect.innerHTML = '<option value="">Selecciona un lugar</option>' + getCollection("lugares").filter(place => place.estado === "activo").map(place => `<option value="${escapeHtml(place.lugares_id)}">${escapeHtml(place.nombre)} — ${escapeHtml(place.ubicacion.direccion)}</option>`).join("");
        }
        if (record) {
            form.elements.title.value = record.titulo || "";
            form.elements.category.value = record.categoria || "";
            form.elements.image.value = record.imagen || "";
            if (article) {
                form.elements.content.value = record.contenido || "";
            } else {
                const [summary, ...content] = String(record.descripcion || "").split(/\n\n/);
                form.elements.summary.value = summary || "";
                form.elements.content.value = content.join("\n\n");
                form.elements.date.value = record.fecha || "";
                form.elements.hourStart.value = record.horarioInicio || "";
                form.elements.hourEnd.value = record.horarioFin || "";
                form.elements.lugar_id.value = record.lugar_id || "";
                form.elements.location.value = record.ubicacion || "";
                form.elements.capacity.value = record.capacidad || "";
                form.elements.requirements.value = record.requisitos || "";
                form.elements.materials.value = record.materiales || "";
            }
            if (user.rol === "administrador") form.elements.estadoInicial.value = record.estado === "publicado" ? "publicado" : "pendiente";
        }
        form.querySelector('[type="submit"]').textContent = record ? "Guardar cambios" : "Enviar a revisión";
        form.addEventListener("submit", event => {
            event.preventDefault();
            if (!form.reportValidity()) return;
            const values = new FormData(form);
            try {
                const image = String(values.get("image") || "");
                if (image && !/^https?:\/\//i.test(image)) throw new Error("La imagen debe ser una URL http o https.");
                if (article) {
                    const input = {
                        titulo: values.get("title"),
                        categoria: values.get("category"),
                        contenido: values.get("content"),
                        imagen: image,
                        estadoInicial: values.get("estadoInicial")
                    };
                    const saved = record ? updateArticle(recordId, input) : createArticle(input);
                    window.location.href = `${detailUrl("articulo", saved.articulos_id)}&${record ? "editado" : "creado"}=1`;
                    return;
                }
                const placeId = String(values.get("lugar_id"));
                const place = getById("lugares", placeId);
                const input = {
                    titulo: values.get("title"),
                    categoria: values.get("category"),
                    descripcion: `${String(values.get("summary")).trim()}\n\n${String(values.get("content")).trim()}`,
                    imagen: image,
                    fecha: values.get("date"),
                    horarioInicio: values.get("hourStart"),
                    horarioFin: values.get("hourEnd"),
                    lugar_id: placeId,
                    ubicacion: values.get("location") || place?.ubicacion?.direccion,
                    capacidad: Number(values.get("capacity")),
                    requisitos: values.get("requirements"),
                    materiales: values.get("materials"),
                    estadoInicial: values.get("estadoInicial")
                };
                const saved = record ? updateVolunteer(recordId, input) : createVolunteer(input);
                window.location.href = `${detailUrl("voluntariado", saved.voluntariados_id)}&${record ? "editado" : "creado"}=1`;
            } catch (error) {
                showError(error.message);
            }
        });
    }

    function initializeDemoDataAndUi() {
        initializeDemoData();
        renderNavigation();
        initializeNotificationUi();
        renderHomeListings();
        renderArticleListing();
        renderFloraListing();
        renderDetailPage();
        initializeCreateForm();
    }

    initializeDemoDataAndUi();

    window.RegeneraContent = {
        keys,
        categories,
        createId,
        getCollection,
        saveCollection,
        getById,
        initializeDemoData,
        getSession,
        getCurrentUser,
        setSession,
        login,
        register,
        logout,
        requireUser,
        createArticle,
        createVolunteer,
        updateArticle,
        updateVolunteer,
        moderateContent,
        updateUser,
        requestOrganizerVerification,
        removeContent,
        updatePlace,
        createPlace,
        removePlace,
        updateFlora,
        createFlora,
        removeFlora,
        canManageVolunteer,
        addNotification,
        audit,
        normalizeCategory,
        getPlace,
        getPublicVolunteers,
        getPublicArticles,
        getPublicFlora,
        getVolunteerParticipants,
        joinVolunteer,
        cancelVolunteerEnrollment,
        markAttendance,
        registerAttendance,
        generateAttendanceCode,
        getAttendanceCode,
        getUserImpact,
        getAchievementProgress,
        checkUserAchievements,
        awardPoints,
        renderAchievementCard,
        visibleDetail,
        renderFloraListing,
        detailUrl,
        escapeHtml,
        formatDate,
        formatTime,
        formatVolunteerDuration,
        statusBadge,
        getUnreadNotifications,
        renderNavigation
    };
})();
