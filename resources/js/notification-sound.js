const preferenceKey = 'regenera_push_sound';
const availableSounds = new Set(['bell', 'double', 'soft', 'none']);
const tones = {
    bell: [
        { frequency: 880, offset: 0, duration: 0.24, type: 'sine' },
        { frequency: 1174, offset: 0.13, duration: 0.32, type: 'sine' },
    ],
    double: [
        { frequency: 740, offset: 0, duration: 0.15, type: 'triangle' },
        { frequency: 740, offset: 0.22, duration: 0.2, type: 'triangle' },
    ],
    soft: [
        { frequency: 523, offset: 0, duration: 0.36, type: 'sine' },
    ],
};

export function getNotificationSound() {
    const preference = localStorage.getItem(preferenceKey);
    return availableSounds.has(preference) ? preference : 'bell';
}

export function setNotificationSound(sound) {
    if (!availableSounds.has(sound)) return;
    localStorage.setItem(preferenceKey, sound);
}

export async function playNotificationSound(sound = getNotificationSound()) {
    if (sound === 'none' || !tones[sound]) return;

    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;

    let context;
    try {
        context = new AudioContext();
        await context.resume();
        const now = context.currentTime;
        let lastToneEnd = 0;

        tones[sound].forEach(tone => {
            const start = now + tone.offset;
            const end = start + tone.duration;
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = tone.type;
            oscillator.frequency.value = tone.frequency;
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(0.14, start + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.0001, end);
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start(start);
            oscillator.stop(end);
            lastToneEnd = Math.max(lastToneEnd, end);
        });

        window.setTimeout(() => { void context.close(); }, Math.max(250, (lastToneEnd - now + 0.1) * 1000));
    } catch {
        if (context && context.state !== 'closed') await context.close();
    }
}