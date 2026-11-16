export function selectProfileMedals(medals) {
    const earned = medals
        .filter(medal => medal.desbloqueada === true)
        .sort((a, b) => (Date.parse(b.fechaObtencion) || 0) - (Date.parse(a.fechaObtencion) || 0));
    const locked = medals.filter(medal => medal.desbloqueada === false && medal.disponibleParaRol !== false);

    return { recent: earned.slice(0, 3), earned, locked };
}

export function ownRecords(records, userId) {
    return records.filter(item => String(item.usuarioId) === String(userId));
}

export function participationStatus(volunteer, enrollment, attendance) {
    if (enrollment.estado === 'cancelada') return { label: 'Inscripción cancelada', kind: 'cancelled' };
    if (volunteer.estado === 'cancelado') return { label: 'Voluntariado cancelado', kind: 'cancelled' };
    if (!['inscrita', 'activa', 'completada'].includes(enrollment.estado)) {
        return { label: 'Estado de inscripción no disponible', kind: 'pending' };
    }
    if (attendance?.confirmada === true) return { label: 'Asistencia confirmada', kind: 'confirmed' };
    if (volunteer.finalizadoPorFecha || volunteer.estado === 'finalizado') {
        return { label: 'Asistencia no confirmada', kind: 'unconfirmed' };
    }
    if (volunteer.estado === 'pendiente') return { label: 'Voluntariado en revisión', kind: 'pending' };
    return { label: 'Inscripción activa', kind: 'pending' };
}
