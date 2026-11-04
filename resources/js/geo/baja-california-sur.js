import boundary from '../../data/baja-california-sur.json';

const { geometry } = boundary;
const positions = geometry.coordinates.flat(2);
const longitudes = positions.map(([longitude]) => longitude);
const latitudes = positions.map(([, latitude]) => latitude);
const polygons = geometry.coordinates.map(rings => {
    const [longitudes, latitudes] = rings[0].reduce(([allLongitudes, allLatitudes], [longitude, latitude]) => {
        allLongitudes.push(longitude);
        allLatitudes.push(latitude);
        return [allLongitudes, allLatitudes];
    }, [[], []]);
    const bounds = [
        Math.min(...longitudes),
        Math.min(...latitudes),
        Math.max(...longitudes),
        Math.max(...latitudes),
    ];

    return {
        rings,
        bounds,
        area: (bounds[2] - bounds[0]) * (bounds[3] - bounds[1]),
    };
}).sort((left, right) => right.area - left.area);

export const bajaCaliforniaSurBounds = [
    [Math.min(...latitudes), Math.min(...longitudes)],
    [Math.max(...latitudes), Math.max(...longitudes)],
];

export function isInsideBajaCaliforniaSur(latitude, longitude) {
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return false;

    const [[minLatitude, minLongitude], [maxLatitude, maxLongitude]] = bajaCaliforniaSurBounds;
    if (latitude < minLatitude || latitude > maxLatitude || longitude < minLongitude || longitude > maxLongitude) {
        return false;
    }

    const point = [longitude, latitude];
    return polygons.some(polygon => {
        const [minLongitude, minLatitude, maxLongitude, maxLatitude] = polygon.bounds;
        if (
            longitude < minLongitude
            || latitude < minLatitude
            || longitude > maxLongitude
            || latitude > maxLatitude
        ) {
            return false;
        }

        return pointInRing(point, polygon.rings[0])
            && !polygon.rings.slice(1).some(hole => pointInRing(point, hole));
    });
}

function pointInRing([longitude, latitude], ring) {
    let inside = false;

    for (let index = 0, previous = ring.length - 1; index < ring.length; previous = index, index += 1) {
        const [startLongitude, startLatitude] = ring[index];
        const [endLongitude, endLatitude] = ring[previous];
        const cross = (longitude - startLongitude) * (endLatitude - startLatitude)
            - (latitude - startLatitude) * (endLongitude - startLongitude);
        const withinSegment = longitude >= Math.min(startLongitude, endLongitude) - 1e-10
            && longitude <= Math.max(startLongitude, endLongitude) + 1e-10
            && latitude >= Math.min(startLatitude, endLatitude) - 1e-10
            && latitude <= Math.max(startLatitude, endLatitude) + 1e-10;

        if (Math.abs(cross) < 1e-10 && withinSegment) return true;

        const intersects = (startLatitude > latitude) !== (endLatitude > latitude)
            && longitude < (endLongitude - startLongitude) * (latitude - startLatitude)
                / (endLatitude - startLatitude) + startLongitude;
        if (intersects) inside = !inside;
    }

    return inside;
}

export { boundary as bajaCaliforniaSurBoundary };
