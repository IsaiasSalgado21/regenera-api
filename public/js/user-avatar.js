(function () {
    function create(name, photo) {
        const avatar = document.createElement('span');
        avatar.className = 'user-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        const initial = document.createElement('span');
        initial.textContent = Array.from(String(name || '').trim())[0]?.toLocaleUpperCase() || '?';
        avatar.append(initial);
        if (typeof photo === 'string' && photo) {
            const image = document.createElement('img');
            image.src = photo;
            image.alt = '';
            image.loading = 'lazy';
            avatar.append(image);
        }
        return avatar;
    }

    document.addEventListener('error', event => {
        if (event.target.matches?.('.user-avatar > img')) event.target.remove();
    }, true);

    window.RegeneraAvatar = { create, markup: (name, photo) => create(name, photo).outerHTML };
})();
