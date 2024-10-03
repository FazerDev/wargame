// Sélectionne l'élément de la carte
const map = document.getElementById('base-outer');

// Variables pour stocker les positions initiales
let isDragging = false;
let startX, startY, initialX, initialY;

// Récupère la taille du conteneur visible et de la carte
function getContainerAndMapSizes() {
    const containerWidth = window.innerWidth; // Largeur de la fenêtre
    const containerHeight = window.innerHeight; // Hauteur de la fenêtre
    const mapWidth = map.offsetWidth; // Largeur de la carte
    const mapHeight = map.offsetHeight; // Hauteur de la carte
    return { containerWidth, containerHeight, mapWidth, mapHeight };
}

// Quand on commence à cliquer
map.addEventListener('mousedown', (e) => {
    isDragging = true;
    startX = e.clientX;
    startY = e.clientY;

    // Stocke la position initiale de la carte
    const matrix = new WebKitCSSMatrix(window.getComputedStyle(map).transform);
    initialX = matrix.m41 || 0;
    initialY = matrix.m42 || 0;
});

// Quand on déplace la souris
window.addEventListener('mousemove', (e) => {
    if (!isDragging) return;

    const { containerWidth, containerHeight, mapWidth, mapHeight } = getContainerAndMapSizes();

    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    // Calcule la nouvelle position
    let newX = initialX + dx;
    let newY = initialY + dy;

    // Appliquer les limites de déplacement
    const maxX = 0; // Bord gauche
    const maxY = 0; // Bord haut
    const minX = containerWidth - mapWidth; // Bord droit
    const minY = containerHeight - mapHeight; // Bord bas

    // Vérifier les limites et ajuster
    if (newX > maxX) newX = maxX;
    if (newY > maxY) newY = maxY;
    if (newX < minX) newX = minX;
    if (newY < minY) newY = minY;

    // Applique la nouvelle position
    map.style.transform = `translate(${newX}px, ${newY}px)`;
});

// Quand on relâche la souris
window.addEventListener('mouseup', () => {
    isDragging = false;
});

document.addEventListener('DOMContentLoaded', function () {
    const refinery = document.querySelector('.refinery');
    const menu = document.getElementById('menu-refinery');
    const closeBtn = document.querySelector('.close-btn');
    const buildBtn = document.getElementById('build-refinery');
    const ownedRefineryCount = document.getElementById('owned-refinery-count');
    const userDollarsDisplay = document.getElementById('dollars');
    const constructionTimerDisplay = document.getElementById('construction-timer');
    let constructionEndTime = null;
    let constructionTimerInterval = null;

    // Fonction pour récupérer le nombre de raffineries
    function fetchOwnedRefineries() {
        fetch('/base_wargame2/assets/api/get_refinery_count.php')
            .then(response => response.json())
            .then(data => {
                console.log("Données reçues pour raffineries :", data); // Log pour vérifier les données

                ownedRefineryCount.textContent = data.refinery_count;

                if (data.construction_end_time) {
                    console.log("Heure de fin de construction (avant conversion) :", data.construction_end_time);

                    constructionEndTime = new Date(data.construction_end_time).getTime();
                    console.log("Heure de fin de construction (timestamp) :", constructionEndTime);

                    startConstructionTimer(); // Démarrer le timer si la construction est en cours
                } else {
                    constructionEndTime = null;
                    clearConstructionTimer();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des raffineries:', error);
            });
    }

    // Fonction pour récupérer les dollars de l'utilisateur
    function fetchUserDollars() {
        fetch('/base_wargame2/assets/api/get_user_dollars.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (userDollarsDisplay) {
                    userDollarsDisplay.textContent = data.dollars;
                    const refineryPrice = 500;
                    const maxRefineries = Math.floor(data.dollars / refineryPrice);
                    document.getElementById('max-refinery-count').textContent = maxRefineries;
                } else {
                    console.error('L\'élément dollars est introuvable');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des dollars:', error);
            });
    }

    // Démarrer le minuteur de construction
    function startConstructionTimer() {
        if (constructionTimerInterval) {
            clearInterval(constructionTimerInterval);
        }

        constructionTimerInterval = setInterval(function () {
            const now = new Date().getTime();
            const timeLeft = constructionEndTime - now;

            console.log("Temps restant (ms) :", timeLeft); // Log pour vérifier le temps restant

            if (timeLeft <= 0) {
                clearInterval(constructionTimerInterval);
                constructionTimerDisplay.textContent = "Construction terminée";
                fetchOwnedRefineries(); // Rafraîchir les données après la construction
                return;
            }

            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            // Met à jour le texte du timer
            constructionTimerDisplay.textContent = `Temps restant: ${minutes}m ${seconds}s`;
            console.log("Mise à jour du timer :", constructionTimerDisplay.textContent); // Log pour vérifier la mise à jour
        }, 1000);
    }


    // Arrêter le minuteur de construction
    function clearConstructionTimer() {
        if (constructionTimerInterval) {
            clearInterval(constructionTimerInterval);
            constructionTimerDisplay.textContent = "Aucune construction en cours";
        }
    }

    // Ouvrir le menu en cliquant sur l'image
    refinery.addEventListener('click', function () {
        fetchOwnedRefineries();
        fetchUserDollars();
        menu.style.display = 'block';
    });

    // Fermer le menu en cliquant sur le bouton de fermeture
    closeBtn.addEventListener('click', function () {
        menu.style.display = 'none';
    });

    // Action lorsque l'utilisateur clique sur "Construire"
    buildBtn.addEventListener('click', function () {
        const refineryCount = document.getElementById('refinery-count').value;

        // Vérifier que la valeur est valide avant l'envoi
        if (refineryCount <= 0) {
            alert('Veuillez entrer un nombre valide de raffineries.');
            return;
        }

        fetch('/base_wargame2/assets/api/build.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                refinery_count: refineryCount
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log("Données de réponse après construction :", data); // Log pour vérifier la réponse après la construction

                if (data.success) {
                    showNotification1('Construction réussie');
                    fetchOwnedRefineries(); // Mettre à jour après la construction
                    fetchUserDollars(); // Mettre à jour les dollars après la construction

                    // Utiliser un délai pour laisser le temps à l'API de mettre à jour
                    setTimeout(() => {
                        fetchOwnedRefineries(); // Mettre à jour après la construction
                        fetchUserDollars(); // Mettre à jour les dollars après la construction
                    }, 2000); // Délai de 2 secondes

                    // Afficher la durée de construction dans le menu
                    const constructionDuration = data.construction_duration; // Durée en minutes
                    constructionTimerDisplay.textContent = `Temps de construction : ${constructionDuration} minutes`;

                    // Déterminer le moment de fin de construction
                    const formattedEndTime = data.construction_end_time.replace(" ", "T");
                    constructionEndTime = new Date(formattedEndTime).getTime();

                    console.log("Heure de fin de construction après conversion (timestamp) :", constructionEndTime);

                    startConstructionTimer(); // Démarrer le compte à rebours
                } else {
                    showNotification((data.error || 'Inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur lors de la construction:', error);
            });
    });
});




document.addEventListener('DOMContentLoaded', function () {
    const ammunitionFactory = document.querySelector('.ammunition_factory');
    const ammoMenu = document.getElementById('menu-ammunition_factory');
    const closeAmmoBtn = ammoMenu.querySelector('.close-btn');
    const buildBtn = document.getElementById('build-ammunition'); // ID corrigé
    const ownedAmmunitionCount = document.getElementById('owned-ammunition-count'); // ID corrigé
    const userDollarsDisplay = document.getElementById('dollars'); // ID mis à jour pour les dollars

    // Fonction pour récupérer le nombre d'usines de munitions
    function fetchOwnedAmmunitionFactories() {
        fetch('/base_wargame2/assets/api/get_ammunition_count.php') // Remplace par l'API correcte
            .then(response => response.json())
            .then(data => {
                ownedAmmunitionCount.textContent = data.ammo_factory_count; // Afficher le nombre d'usines
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des usines de munitions:', error);
            });
    }

    // Fonction pour récupérer les dollars de l'utilisateur
    function fetchUserDollars() {
        fetch('/base_wargame2/assets/api/get_user_dollars.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (userDollarsDisplay) {
                    userDollarsDisplay.textContent = data.dollars; // Afficher les dollars
                    // Calculer le nombre maximal d'usines de munitions pouvant être construites
                    const ammunitionPrice = 500; // Prix d'une usine de munitions
                    const maxAmmunitionFactories = Math.floor(data.dollars / ammunitionPrice);
                    document.getElementById('max-ammunition-count').textContent = maxAmmunitionFactories; // Afficher le nombre maximum
                } else {
                    console.error('L\'élément dollars est introuvable');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des dollars:', error);
            });
    }

    // Ouvrir le menu en cliquant sur l'image
    ammunitionFactory.addEventListener('click', function () {
        fetchOwnedAmmunitionFactories(); // Récupérer le nombre d'usines de munitions
        fetchUserDollars(); // Récupérer les dollars lorsque le menu s'ouvre
        ammoMenu.style.display = 'block';
    });

    // Fermer le menu en cliquant sur le bouton de fermeture
    closeAmmoBtn.addEventListener('click', function () {
        ammoMenu.style.display = 'none';
    });

    // Action lorsque l'utilisateur clique sur "Construire"
    buildBtn.addEventListener('click', function () {
        const ammunitionCount = document.getElementById('ammunition-count').value; // ID corrigé

        // Vérifier que la valeur est valide avant l'envoi
        if (ammunitionCount <= 0) {
            alert('Veuillez entrer un nombre valide d\'usines de munitions.');
            return;
        }

        fetch('/base_wargame2/assets/api/build_ammunition.php', { // Chemin vers le fichier build_ammunition.php
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ammunition_count: ammunitionCount // Variable mise à jour
            })
        })
            .then(response => {
                // Si ce n'est pas une réponse JSON valide, lance une erreur
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Réponse reçue:', data); // Pour déboguer
                if (data.success) {
                    showNotification1('Construction réussie');
                    fetchOwnedAmmunitionFactories(); // Mettre à jour après la construction
                    fetchUserDollars(); // Mettre à jour les dollars après la construction
                } else {
                    showNotification(data.error || 'Inconnue');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la construction:', error);
            });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const mineBuilding = document.querySelector('.mine'); // Sélectionne l'élément représentant la mine
    const mineMenu = document.getElementById('menu-mine');
    const closeMineBtn = mineMenu.querySelector('.close-btn');
    const buildMineBtn = document.getElementById('build-mine'); // ID pour construire la mine
    const ownedMineCount = document.getElementById('owned-mine-count'); // ID pour le nombre de mines possédées
    const userDollarsDisplay = document.getElementById('dollars'); // ID mis à jour pour les dollars

    // Fonction pour récupérer le nombre de mines d'or
    function fetchOwnedMines() {
        fetch('/base_wargame2/assets/api/get_mine_count.php') // Remplace par l'API correcte pour les mines
            .then(response => response.json())
            .then(data => {
                ownedMineCount.textContent = data.mine_count; // Afficher le nombre de mines d'or
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des mines:', error);
            });
    }

    // Fonction pour récupérer les dollars de l'utilisateur
    function fetchUserDollars() {
        fetch('/base_wargame2/assets/api/get_user_dollars.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (userDollarsDisplay) {
                    userDollarsDisplay.textContent = data.dollars; // Afficher les dollars
                    // Calculer le nombre maximal de mines d'or pouvant être construites
                    const minePrice = 1000; // Prix d'une mine d'or
                    const maxMines = Math.floor(data.dollars / minePrice);
                    document.getElementById('max-mine-count').textContent = maxMines; // Afficher le nombre maximum
                } else {
                    console.error('L\'élément dollars est introuvable');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des dollars:', error);
            });
    }

    // Ouvrir le menu en cliquant sur l'image de la mine
    mineBuilding.addEventListener('click', function () {
        fetchOwnedMines(); // Récupérer le nombre de mines d'or
        fetchUserDollars(); // Récupérer les dollars lorsque le menu s'ouvre
        mineMenu.style.display = 'block';
    });

    // Fermer le menu en cliquant sur le bouton de fermeture
    closeMineBtn.addEventListener('click', function () {
        mineMenu.style.display = 'none';
    });

    // Action lorsque l'utilisateur clique sur "Construire"
    buildMineBtn.addEventListener('click', function () {
        const mineCount = document.getElementById('mine-count').value; // ID corrigé pour la mine

        // Vérifier que la valeur est valide avant l'envoi
        if (mineCount <= 0) {
            alert('Veuillez entrer un nombre valide de mines.');
            return;
        }

        fetch('/base_wargame2/assets/api/build_mine.php', { // Chemin vers le fichier build_mine.php
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                mine_count: mineCount // Variable mise à jour pour la mine
            })
        })
            .then(response => {
                // Si ce n'est pas une réponse JSON valide, lance une erreur
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Réponse reçue:', data); // Pour déboguer
                if (data.success) {
                    showNotification1('Construction réussie');
                    fetchOwnedMines(); // Mettre à jour après la construction
                    fetchUserDollars(); // Mettre à jour les dollars après la construction
                } else {
                    showNotification(data.error || 'Erreur inconnue');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la construction:', error);
            });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const tradeBuilding = document.querySelector('.trade'); // Sélectionne l'élément représentant le trade
    const tradeMenu = document.getElementById('menu-trade');
    const closeTradeBtn = tradeMenu.querySelector('.close-btn');
    const buildTradeBtn = document.getElementById('build-trade'); // ID pour construire le trade
    const ownedTradeCount = document.getElementById('owned-trade-count'); // ID pour le nombre de trades possédés
    const userDollarsDisplay = document.getElementById('dollars'); // ID mis à jour pour les dollars

    // Fonction pour récupérer le nombre de gratte-ciels (trade_count)
    function fetchOwnedTrades() {
        fetch('/base_wargame2/assets/api/get_trade_count.php') // API correcte pour les gratte-ciels (trade_count)
            .then(response => response.json())
            .then(data => {
                ownedTradeCount.textContent = data.trade_count; // Afficher le nombre de gratte-ciels
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des gratte-ciels:', error);
            });
    }

    // Fonction pour récupérer les dollars de l'utilisateur
    function fetchUserDollars() {
        fetch('/base_wargame2/assets/api/get_user_dollars.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (userDollarsDisplay) {
                    userDollarsDisplay.textContent = data.dollars; // Afficher les dollars
                    const tradePrice = 2000; // Exemple de prix d'un gratte-ciel (trade)
                    const maxTrades = Math.floor(data.dollars / tradePrice);
                    document.getElementById('max-trade-count').textContent = maxTrades; // Afficher le nombre maximal de gratte-ciels
                } else {
                    console.error('L\'élément dollars est introuvable');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des dollars:', error);
            });
    }

    // Ouvrir le menu en cliquant sur l'image de trade
    tradeBuilding.addEventListener('click', function () {
        fetchOwnedTrades(); // Récupérer le nombre de gratte-ciels
        fetchUserDollars(); // Récupérer les dollars lorsque le menu s'ouvre
        tradeMenu.style.display = 'block';
    });

    // Fermer le menu en cliquant sur le bouton de fermeture
    closeTradeBtn.addEventListener('click', function () {
        tradeMenu.style.display = 'none';
    });

    // Action lorsque l'utilisateur clique sur "Construire"
    buildTradeBtn.addEventListener('click', function () {
        const tradeCount = document.getElementById('trade-count').value;

        if (tradeCount <= 0) {
            alert('Veuillez entrer un nombre valide de gratte-ciels.');
            return;
        }

        fetch('/base_wargame2/assets/api/build_trade.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                trade_count: tradeCount // Utilisation de trade_count
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification1('Construction réussie');
                    fetchOwnedTrades(); // Mettre à jour après la construction
                    fetchUserDollars(); // Mettre à jour les dollars après la construction
                } else {
                    showNotification(data.error || 'Erreur inconnue');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la construction:', error);
            });
    });
});



function showNotification(message) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.style.display = 'block';
    notification.style.opacity = '1';

    // Masquer la notification après quelques secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 500); // Attendre que l'animation se termine
    }, 3000); // Afficher la notification pendant 3 secondes
}

function showNotification1(message) {
    const notification1 = document.getElementById('notification1');
    notification1.textContent = message;
    notification1.style.display = 'block';
    notification1.style.opacity = '1';

    // Masquer la notification après quelques secondes
    setTimeout(() => {
        notification1.style.opacity = '0';
        setTimeout(() => {
            notification1.style.display = 'none';
        }, 500); // Attendre que l'animation se termine
    }, 3000); // Afficher la notification pendant 3 secondes
}