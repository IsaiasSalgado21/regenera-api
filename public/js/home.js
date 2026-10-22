/**
 * Carrusel de flora y fauna del hero (home).
 *
 * Los datos vienen del backend (HomeController) y se embeben como JSON
 * en un <script type="application/json"> para no depender de contenido.js.
 */
(function () {
    const dataNode = document.getElementById("floraData");
    if (!dataNode) return;

    let floraData = [];
    try {
        floraData = JSON.parse(dataNode.textContent) || [];
    } catch (error) {
        console.error("No se pudo leer el JSON de flora/fauna.", error);
        return;
    }

    const floraPanel = document.getElementById("floraPanel");
    if (!floraPanel || !floraData.length) return;

    const floraBackground = floraPanel.querySelector(".panel-background");
    const floraTitle = document.getElementById("floraTitle");
    const floraDescription = document.getElementById("floraDescription");
    const floraCurrent = document.getElementById("floraCurrent");
    const floraTotal = document.getElementById("floraTotal");
    const floraDetailLink = document.getElementById("floraDetailLink");
    const baseUrl = floraPanel.dataset.baseUrl || "";

    let floraIndex = 0;
    let floraTimer;

    function changeFlora(index) {
        const species = floraData[index];
        if (!species) return;

        floraBackground.style.opacity = "0";
        window.setTimeout(() => {
            if (species.imagen) {
                floraBackground.style.backgroundImage = `url("${species.imagen}")`;
            }
            floraTitle.textContent = species.nombre || "";
            floraDescription.textContent = species.descripcion || "";
            floraDetailLink.href = `${baseUrl}/flora/${species.id}`;
            floraCurrent.textContent = String(index + 1).padStart(2, "0");
            floraBackground.style.opacity = "1";
        }, 350);
    }

    function nextFlora() {
        floraIndex = (floraIndex + 1) % floraData.length;
        changeFlora(floraIndex);
    }

    function previousFlora() {
        floraIndex = (floraIndex - 1 + floraData.length) % floraData.length;
        changeFlora(floraIndex);
    }

    function startFloraTimer() {
        window.clearInterval(floraTimer);
        floraTimer = window.setInterval(nextFlora, 6000);
    }

    floraTotal.textContent = String(floraData.length).padStart(2, "0");
    changeFlora(floraIndex);

    document.getElementById("floraNext").addEventListener("click", event => {
        event.stopPropagation();
        nextFlora();
        startFloraTimer();
    });

    document.getElementById("floraPrev").addEventListener("click", event => {
        event.stopPropagation();
        previousFlora();
        startFloraTimer();
    });

    floraPanel.addEventListener("mouseenter", () => window.clearInterval(floraTimer));
    floraPanel.addEventListener("mouseleave", startFloraTimer);

    if (floraData.length > 1) startFloraTimer();
})();