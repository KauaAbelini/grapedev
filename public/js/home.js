// =====================================================
// HEADER / SCROLL
// =====================================================

window.addEventListener("scroll", () => {

    const header = document.getElementById("main-header");

    if (!header) return;

    if (window.scrollY > 50) {

        header.style.background = "var(--bg-header)";
        header.style.padding = "0.5rem 0";

    } else {

        header.style.background = "var(--bg-header)";
        header.style.padding = "1rem 0";

    }

});


// =====================================================
// MENU MOBILE
// =====================================================

const menuBtn = document.getElementById("menuBtn");
const navLinks = document.getElementById("navLinks");

if (menuBtn && navLinks) {

    menuBtn.addEventListener("click", () => {

        navLinks.classList.toggle("active");

    });

    navLinks.querySelectorAll("a").forEach((link) => {

        link.addEventListener("click", () => {

            if (navLinks.classList.contains("active")) {

                navLinks.classList.remove("active");

            }

        });

    });

}


// =====================================================
// ANIMAÇÃO FADE-IN
// =====================================================

const observerOptions = {

    threshold: 0.15,

    rootMargin: "0px 0px -50px 0px"

};

const observer = new IntersectionObserver(

    (entries) => {

        entries.forEach((entry) => {

            if (entry.isIntersecting) {

                entry.target.classList.add("visible");

                observer.unobserve(entry.target);

            }

        });

    },

    observerOptions

);

document.querySelectorAll(".fade-in").forEach((el) => {

    observer.observe(el);

});


// =====================================================
// BUSCA DE PROJETOS
// =====================================================

const searchBtn = document.getElementById("searchBtn");
const searchInput = document.getElementById("searchInput");

function pesquisarProjeto() {

    if (!searchInput) return;

    const value = searchInput.value
        .toLowerCase()
        .trim();

    if (!value) {

        return;

    }

    if (
        value.includes("aqua") ||
        value.includes("água") ||
        value.includes("agua")
    ) {

        window.location.href = "aquaflow.html";

    }

    else if (value.includes("pet")) {

        window.location.href = "petcontrol.html";

    }

    else if (
        value.includes("bem") ||
        value.includes("academia")
    ) {

        window.location.href = "bemestar.html";

    }

    else if (
        value.includes("dashboard") ||
        value.includes("painel") ||
        value.includes("conta")
    ) {

        window.location.href = "login.php";

    }

    else if (
        value.includes("projeto") ||
        value.includes("projetos")
    ) {

        window.location.href = "projetos.html";

    }

    else {

        alert(
            "Projeto não encontrado.\n\n" +
            "Tente buscar por:\n" +
            "• AquaFlow\n" +
            "• PetControl\n" +
            "• BemEstar\n" +
            "• Dashboard"
        );

    }

}

if (searchBtn && searchInput) {

    searchBtn.addEventListener(
        "click",
        pesquisarProjeto
    );

    searchInput.addEventListener(
        "keypress",
        (e) => {

            if (e.key === "Enter") {

                pesquisarProjeto();

            }

        }
    );

}


// =====================================================
// TEMA CLARO / ESCURO
// =====================================================

const themeToggle = document.getElementById("themeToggle");

const savedTheme = localStorage.getItem("grape-theme");

if (savedTheme === "light") {

    document.body.classList.add("light-theme");

}

if (themeToggle) {

    themeToggle.addEventListener("click", () => {

        document.body.classList.toggle("light-theme");

        themeToggle.classList.add("change");

        setTimeout(() => {

            themeToggle.classList.remove("change");

        }, 500);

        if (
            document.body.classList.contains("light-theme")
        ) {

            localStorage.setItem(
                "grape-theme",
                "light"
            );

        } else {

            localStorage.setItem(
                "grape-theme",
                "dark"
            );

        }

    });

}


// =====================================================
// LOGIN / CADASTRO
// =====================================================
//
// IMPORTANTE:
// O link Login / Cadastro deve abrir login.php.
//
// Depois que o usuário fizer login corretamente,
// o login.php envia ele de volta para home.html.
// A sessão PHP continua ativa.
//

const linksConta = document.querySelectorAll("a");

linksConta.forEach((link) => {

    const texto = link.textContent
        .toLowerCase()
        .trim();

    if (
        texto.includes("login") ||
        texto.includes("cadastro") ||
        texto.includes("entrar") ||
        texto.includes("login / cadastro") ||
        texto.includes("login/cadastro")
    ) {

        link.setAttribute("href", "login.php");

    }

});


// =====================================================
// FEEDBACK
// =====================================================

const feedbackForm =
    document.getElementById("feedbackForm");

const feedbackName =
    document.getElementById("feedbackName");

const feedbackMessage =
    document.getElementById("feedbackMessage");

const feedbackStatus =
    document.getElementById("feedbackStatus");

const feedbackModal =
    document.getElementById("feedbackModal");

const showFeedbacks =
    document.getElementById("showFeedbacks");

const closeFeedback =
    document.getElementById("closeFeedback");

const feedbackList =
    document.getElementById("feedbackList");


// =====================================================
// ESTRELAS DO FEEDBACK
// =====================================================

const ratingStars =
    document.querySelectorAll(".star-btn");

const feedbackRating =
    document.getElementById("feedbackRating");

let notaSelecionada = 5;


function atualizarEstrelas(nota) {

    ratingStars.forEach((estrela) => {

        const valor =
            Number(estrela.dataset.rating);

        if (valor <= nota) {

            estrela.classList.add("active");

        } else {

            estrela.classList.remove("active");

        }

    });

}


ratingStars.forEach((estrela) => {

    estrela.addEventListener("click", () => {

        notaSelecionada =
            Number(estrela.dataset.rating);

        if (feedbackRating) {

            feedbackRating.value =
                notaSelecionada;

        }

        atualizarEstrelas(notaSelecionada);

    });

});


atualizarEstrelas(5);


// =====================================================
// LOCALSTORAGE DOS FEEDBACKS
// =====================================================

function pegarFeedbacks() {

    try {

        return JSON.parse(
            localStorage.getItem("grape-feedbacks")
        ) || [];

    } catch (error) {

        return [];

    }

}


function salvarFeedbacks(feedbacks) {

    localStorage.setItem(
        "grape-feedbacks",
        JSON.stringify(feedbacks)
    );

}


// =====================================================
// MOSTRAR FEEDBACKS
// =====================================================

function mostrarFeedbacks() {

    if (!feedbackList) return;

    const feedbacks =
        pegarFeedbacks();

    feedbackList.innerHTML = "";


    if (feedbacks.length === 0) {

        const mensagem =
            document.createElement("div");

        mensagem.className =
            "no-feedback";

        mensagem.textContent =
            "Ainda não existem comentários. Seja o primeiro a deixar seu feedback!";

        feedbackList.appendChild(
            mensagem
        );

        return;

    }


    feedbacks.forEach((feedback) => {

        const card =
            document.createElement("div");

        card.className =
            "feedback-card";


        const header =
            document.createElement("div");

        header.className =
            "feedback-card-header";


        const name =
            document.createElement("span");

        name.className =
            "feedback-name";

        name.textContent =
            feedback.name;


        const date =
            document.createElement("span");

        date.className =
            "feedback-date";

        date.textContent =
            feedback.date;


        header.appendChild(name);

        header.appendChild(date);


        const stars =
            document.createElement("div");

        stars.className =
            "feedback-card-stars";


        const nota =
            Math.max(
                1,
                Math.min(
                    5,
                    Number(feedback.rating) || 5
                )
            );


        stars.textContent =
            "★".repeat(nota) +
            "☆".repeat(5 - nota);


        const message =
            document.createElement("div");

        message.className =
            "feedback-card-message";

        message.textContent =
            feedback.message;


        card.appendChild(header);

        card.appendChild(stars);

        card.appendChild(message);

        feedbackList.appendChild(card);

    });

}


// =====================================================
// ENVIAR FEEDBACK
// =====================================================

if (feedbackForm) {

    feedbackForm.addEventListener(
        "submit",
        (event) => {

            event.preventDefault();


            const name =
                feedbackName.value.trim();

            const message =
                feedbackMessage.value.trim();


            if (!name || !message) {

                if (feedbackStatus) {

                    feedbackStatus.textContent =
                        "Preencha seu nome e comentário.";

                }

                return;

            }


            const feedbacks =
                pegarFeedbacks();


            const novoFeedback = {

                name: name,

                rating: notaSelecionada,

                message: message,

                date: new Date()
                    .toLocaleDateString("pt-BR")

            };


            feedbacks.unshift(
                novoFeedback
            );


            salvarFeedbacks(
                feedbacks
            );


            feedbackForm.reset();


            notaSelecionada = 5;

            if (feedbackRating) {

                feedbackRating.value = 5;

            }

            atualizarEstrelas(5);


            if (feedbackStatus) {

                feedbackStatus.textContent =
                    "✓ Obrigado pelo seu feedback!";

                setTimeout(() => {

                    feedbackStatus.textContent =
                        "";

                }, 4000);

            }

        }
    );

}


// =====================================================
// ABRIR HISTÓRICO DE FEEDBACKS
// =====================================================

if (showFeedbacks && feedbackModal) {

    showFeedbacks.addEventListener(
        "click",
        () => {

            mostrarFeedbacks();

            feedbackModal.classList.add(
                "active"
            );

            feedbackModal.setAttribute(
                "aria-hidden",
                "false"
            );

            document.body.classList.add(
                "modal-open"
            );

        }
    );

}


// =====================================================
// FECHAR FEEDBACKS
// =====================================================

function fecharFeedbacks() {

    if (!feedbackModal) return;

    feedbackModal.classList.remove(
        "active"
    );

    feedbackModal.setAttribute(
        "aria-hidden",
        "true"
    );

    document.body.classList.remove(
        "modal-open"
    );

}


if (closeFeedback) {

    closeFeedback.addEventListener(
        "click",
        fecharFeedbacks
    );

}


if (feedbackModal) {

    feedbackModal.addEventListener(
        "click",
        (event) => {

            if (
                event.target === feedbackModal
            ) {

                fecharFeedbacks();

            }

        }
    );

}


// =====================================================
// ESC FECHA MODAL
// =====================================================

document.addEventListener(
    "keydown",
    (event) => {

        if (
            event.key === "Escape" &&
            feedbackModal &&
            feedbackModal.classList.contains(
                "active"
            )
        ) {

            fecharFeedbacks();

        }

    }
);