const messages =
    document.getElementById("messages");

const input =
    document.getElementById("messageInput");

const sendButton =
    document.getElementById("sendButton");

const voiceButton =
    document.getElementById("voiceButton");

const wakeStatus =
    document.getElementById("wakeStatus");

const nexCenterImage =
    document.getElementById("nexCenterImage");

const speakingIndicator =
    document.getElementById("speakingIndicator");

const closePhone =
    document.getElementById("closePhone");

const phone =
    document.querySelector(".phone");


/* =====================================================
   ESTADOS
===================================================== */

let nexAwake = false;

let wakeListening = false;

let audioContext = null;

let analyser = null;

let microphone = null;

let recognition = null;

let falando = false;

let reconhecimentoAtivo = false;

let tentandoReconectar = false;

let audioAtual = null;


/* =====================================================
   ADICIONAR MENSAGEM
===================================================== */

function adicionarMensagem(texto, tipo) {

    if (!messages) return;

    const div =
        document.createElement("div");

    div.className =
        "message " + tipo;


    const small =
        document.createElement("small");

    small.textContent =
        tipo === "nex"
            ? "NEX"
            : "VOCÊ";


    const textoDiv =
        document.createElement("div");

    textoDiv.textContent =
        texto;


    div.appendChild(small);

    div.appendChild(textoDiv);

    messages.appendChild(div);


    messages.scrollTop =
        messages.scrollHeight;
}


/* =====================================================
   VOZ DO NEX
===================================================== */

async function falarNex(texto) {

    if (!texto) return;


    try {

        falando = true;


        if (speakingIndicator) {

            speakingIndicator
                .classList
                .add("active");

        }


        /*
         * Enquanto o Nex fala,
         * paramos o reconhecimento para
         * ele não ouvir a própria voz.
         */

        pararReconhecimento();


        const resposta =
            await fetch(
                "api/voz.php",
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/json"
                    },

                    body:
                        JSON.stringify({
                            text: texto
                        })
                }
            );


        if (!resposta.ok) {

            throw new Error(
                "Erro ao gerar voz: " +
                resposta.status
            );

        }


        const audioBlob =
            await resposta.blob();


        const audioURL =
            URL.createObjectURL(
                audioBlob
            );


        const audio =
            new Audio(audioURL);


        audioAtual = audio;

        audio.volume = 1;


        audio.onplay = function() {

            document.body
                .classList
                .add("speaking");

        };


        audio.onended = function() {

            document.body
                .classList
                .remove("speaking");


            if (speakingIndicator) {

                speakingIndicator
                    .classList
                    .remove("active");

            }


            falando = false;

            audioAtual = null;


            URL.revokeObjectURL(
                audioURL
            );


            /*
             * Depois que o Nex termina de falar,
             * ele volta a ouvir.
             */

            if (nexAwake) {

                setTimeout(
                    function() {

                        iniciarReconhecimento();

                    },
                    300
                );

            }

        };


        audio.onerror = function() {

            document.body
                .classList
                .remove("speaking");


            if (speakingIndicator) {

                speakingIndicator
                    .classList
                    .remove("active");

            }


            falando = false;

            audioAtual = null;


            URL.revokeObjectURL(
                audioURL
            );


            if (nexAwake) {

                iniciarReconhecimento();

            }

        };


        await audio.play();

    }

    catch (erro) {

        console.error(
            "Erro na voz do Nex:",
            erro
        );


        falando = false;


        if (speakingIndicator) {

            speakingIndicator
                .classList
                .remove("active");

        }


        document.body
            .classList
            .remove("speaking");


        /*
         * Mesmo se a voz falhar,
         * continua ouvindo.
         */

        if (nexAwake) {

            iniciarReconhecimento();

        }

    }

}


/* =====================================================
   RESPONDER IA
===================================================== */

async function responder() {

    const texto =
        input.value.trim();


    if (!texto) return;


    /*
     * Se ainda estiver dormindo
     */

    if (!nexAwake) {

        adicionarMensagem(
            "Estou dormindo... bata palmas para me acordar. 💤👏",
            "nex"
        );

        return;

    }


    /*
     * Para reconhecimento enquanto
     * processamos a pergunta.
     */

    pararReconhecimento();


    adicionarMensagem(
        texto,
        "user"
    );


    input.value = "";


    /*
     * Mensagem pensando
     */

    const pensando =
        document.createElement("div");


    pensando.className =
        "message nex";


    pensando.innerHTML = `
        <small>NEX</small>
        Estou pensando... 🤔
    `;


    messages.appendChild(
        pensando
    );


    messages.scrollTop =
        messages.scrollHeight;


    sendButton.disabled =
        true;


    try {

        const resposta =
            await fetch(
                "api/chat.php",
                {

                    method: "POST",

                    headers: {

                        "Content-Type":
                            "application/json"

                    },

                    body:
                        JSON.stringify({

                            message:
                                texto

                        })

                }
            );


        if (!resposta.ok) {

            throw new Error(
                "Erro HTTP " +
                resposta.status
            );

        }


        const dados =
            await resposta.json();


        if (dados.error) {

            throw new Error(
                dados.error
            );

        }


        pensando.remove();


        const respostaNex =
            dados.response;


        if (!respostaNex) {

            throw new Error(
                "A IA não retornou resposta."
            );

        }


        /*
         * Mostra a resposta
         */

        adicionarMensagem(
            respostaNex,
            "nex"
        );


        /*
         * Faz o Nex falar
         */

        await falarNex(
            respostaNex
        );

    }

    catch (erro) {

        console.error(
            "Erro na comunicação:",
            erro
        );


        if (pensando) {

            pensando.remove();

        }


        const mensagemErro =
            "Desculpe, tive um problema para acessar meu cérebro. 🤖";


        adicionarMensagem(
            mensagemErro,
            "nex"
        );


        await falarNex(
            mensagemErro
        );

    }

    finally {

        sendButton.disabled =
            false;

    }

}


/* =====================================================
   BOTÃO ENVIAR
===================================================== */

if (sendButton) {

    sendButton.addEventListener(
        "click",
        responder
    );

}


/* =====================================================
   ENTER
===================================================== */

if (input) {

    input.addEventListener(
        "keydown",
        function(event) {

            if (
                event.key === "Enter"
            ) {

                event.preventDefault();

                responder();

            }

        }
    );

}


/* =====================================================
   BOTÕES RÁPIDOS
===================================================== */

document
    .querySelectorAll(".suggestion")
    .forEach(
        function(button) {

            button.addEventListener(
                "click",
                function() {

                    input.value =
                        button.dataset.message;

                    responder();

                }
            );

        }
    );


/* =====================================================
   RECONHECIMENTO DE VOZ
===================================================== */

const Recognition =
    window.SpeechRecognition ||
    window.webkitSpeechRecognition;


if (Recognition) {

    recognition =
        new Recognition();


    recognition.lang =
        "pt-BR";


    /*
     * Fica ouvindo continuamente.
     */

    recognition.continuous =
        true;


    recognition.interimResults =
        false;


    recognition.maxAlternatives =
        1;


    /* -----------------------------------------------
       COMEÇOU A OUVIR
    ------------------------------------------------ */

    recognition.onstart =
        function() {

            reconhecimentoAtivo =
                true;


            tentandoReconectar =
                false;


            if (voiceButton) {

                voiceButton.textContent =
                    "🔴";

                voiceButton.style.boxShadow =
                    "0 0 15px #ff3b3b";

            }


            if (wakeStatus && nexAwake) {

                wakeStatus.textContent =
                    "🎙️ Estou ouvindo...";

            }

        };


    /* -----------------------------------------------
       RESULTADO
    ------------------------------------------------ */

    recognition.onresult =
        function(event) {

            if (!nexAwake) {
                return;
            }


            if (falando) {
                return;
            }


            /*
             * Pega o último resultado
             */

            const ultimo =
                event.results[
                    event.results.length - 1
                ];


            if (!ultimo || !ultimo.isFinal) {
                return;
            }


            const texto =
                ultimo[0]
                    .transcript
                    .trim();


            if (!texto) {
                return;
            }


            console.log(
                "🎙️ Nex ouviu:",
                texto
            );


            /*
             * Mostra no input
             */

            input.value =
                texto;


            /*
             * Manda para a IA
             */

            responder();

        };


    /* -----------------------------------------------
       ERRO
    ------------------------------------------------ */

    recognition.onerror =
        function(event) {

            console.error(
                "Erro no reconhecimento:",
                event.error
            );


            reconhecimentoAtivo =
                false;


            if (voiceButton) {

                voiceButton.textContent =
                    "🎙";

                voiceButton.style.boxShadow =
                    "";

            }


            /*
             * Alguns erros são normais.
             */

            if (
                event.error === "not-allowed" ||
                event.error === "service-not-allowed"
            ) {

                if (wakeStatus) {

                    wakeStatus.textContent =
                        "⚠️ Permita o uso do microfone.";

                }

            }

        };


    /* -----------------------------------------------
       TERMINOU DE OUVIR
    ------------------------------------------------ */

    recognition.onend =
        function() {

            reconhecimentoAtivo =
                false;


            if (voiceButton) {

                voiceButton.textContent =
                    "🎙";

                voiceButton.style.boxShadow =
                    "";

            }


            /*
             * Se o Nex estiver acordado e não
             * estiver falando, volta a ouvir.
             */

            if (
                nexAwake &&
                !falando &&
                !tentandoReconectar
            ) {

                tentandoReconectar =
                    true;


                setTimeout(
                    function() {

                        tentandoReconectar =
                            false;


                        iniciarReconhecimento();

                    },
                    500
                );

            }

        };

}


/* =====================================================
   INICIAR RECONHECIMENTO
===================================================== */

function iniciarReconhecimento() {

    if (!recognition) {

        console.warn(
            "SpeechRecognition não disponível."
        );

        return;

    }


    if (!nexAwake) {
        return;
    }


    if (falando) {
        return;
    }


    if (reconhecimentoAtivo) {
        return;
    }


    try {

        recognition.start();


        console.log(
            "🎙️ Nex começou a ouvir."
        );

    }

    catch (erro) {

        console.log(
            "Reconhecimento já está ativo."
        );

    }

}


/* =====================================================
   PARAR RECONHECIMENTO
===================================================== */

function pararReconhecimento() {

    if (!recognition) {
        return;
    }


    try {

        recognition.stop();

    }

    catch (erro) {

        console.log(erro);

    }


    reconhecimentoAtivo =
        false;

}


/* =====================================================
   MICROFONE / PALMA
===================================================== */

async function ativarEscutaPalma() {

    try {

        const stream =
            await navigator
                .mediaDevices
                .getUserMedia({
                    audio: {
                        echoCancellation: false,
                        noiseSuppression: false,
                        autoGainControl: false
                    }
                });


        audioContext =
            new (
                window.AudioContext ||
                window.webkitAudioContext
            )();


        analyser =
            audioContext
                .createAnalyser();


        analyser.fftSize =
            2048;


        analyser.smoothingTimeConstant =
            0.05;


        microphone =
            audioContext
                .createMediaStreamSource(
                    stream
                );


        microphone.connect(
            analyser
        );


        wakeListening =
            true;


        if (wakeStatus) {

            wakeStatus.textContent =
                "👏 Bata uma palma para acordar o Nex";

        }


        detectarPalma();

    }

    catch (erro) {

        console.error(
            "Erro no microfone:",
            erro
        );


        if (wakeStatus) {

            wakeStatus.textContent =
                "⚠️ Permita o acesso ao microfone para continuar.";

        }

    }

}


/* =====================================================
   DETECTOR DE PALMA
===================================================== */

function detectarPalma() {

    if (
        !wakeListening ||
        nexAwake
    ) {

        return;

    }


    const buffer =
        new Uint8Array(
            analyser.fftSize
        );


    let ultimoPico =
        0;

    let ultimoClap =
        0;


    function analisar() {

        if (
            !wakeListening ||
            nexAwake
        ) {

            return;

        }


        analyser.getByteTimeDomainData(
            buffer
        );


        let maiorPico =
            0;


        let soma =
            0;


        for (
            let i = 0;
            i < buffer.length;
            i++
        ) {

            const valor =
                Math.abs(
                    buffer[i] - 128
                );


            soma +=
                valor;


            if (
                valor >
                maiorPico
            ) {

                maiorPico =
                    valor;

            }

        }


        const media =
            soma / buffer.length;


        const agora =
            performance.now();


        /*
         * Uma palma possui:
         *
         * - pico alto
         * - ataque rápido
         * - diferença grande entre
         *   o nível normal e o pico
         *
         * Isso ajuda a ignorar parte
         * do barulho ambiente.
         */

        const picoForte =
            maiorPico > 62 &&
            maiorPico > media * 4;


        if (picoForte) {

            /*
             * Evita detectar o mesmo
             * som várias vezes.
             */

            if (
                agora -
                ultimoClap >
                450
            ) {

                ultimoClap =
                    agora;


                ultimoPico =
                    maiorPico;


                console.log(
                    "👏 Possível palma:",
                    maiorPico
                );


                /*
                 * Confirma a palma.
                 */

                setTimeout(
                    function() {

                        if (
                            !nexAwake &&
                            wakeListening
                        ) {

                            acordarNex();

                        }

                    },
                    80
                );

            }

        }


        requestAnimationFrame(
            analisar
        );

    }


    analisar();

}


/* =====================================================
   ACORDAR NEX
===================================================== */

async function acordarNex() {

    if (nexAwake) {
        return;
    }


    nexAwake =
        true;


    wakeListening =
        false;


    console.log(
        "⚡ Nex acordando..."
    );


    /*
     * Para detector de palma
     */

    if (audioContext) {

        try {

            await audioContext.suspend();

        }

        catch (erro) {

            console.log(erro);

        }

    }


    /*
     * Animação
     */

    document.body
        .classList
        .add("waking");


    if (wakeStatus) {

        wakeStatus.textContent =
            "⚡ Nex acordando...";

    }


    /*
     * Troca para Nex normal
     */

    if (nexCenterImage) {

        nexCenterImage.classList.add(
            "wake-change"
        );


        setTimeout(
            function() {

                nexCenterImage.src =
                    "expressoes/nex_normal.png";

            },
            300
        );

    }


    /*
     * Acende o ambiente
     */

    setTimeout(
        function() {

            document.body
                .classList
                .add("awake");

        },
        200
    );


    /*
     * Depois de acordar
     */

    setTimeout(
        async function() {

            document.body
                .classList
                .remove("waking");


            if (wakeStatus) {

                wakeStatus.textContent =
                    "🎙️ Estou ouvindo...";

            }


            const mensagem =
                "Ah... olá! Eu acordei. " +
                "Estou ouvindo você. Como posso ajudar?";


            adicionarMensagem(
                mensagem,
                "nex"
            );


            /*
             * Nex fala
             */

            await falarNex(
                mensagem
            );


        },
        1500
    );

}


/* =====================================================
   ATIVAR MICROFONE AUTOMATICAMENTE
===================================================== */

/*
 * IMPORTANTE:
 *
 * O navegador pode exigir uma interação
 * do usuário antes de liberar o microfone.
 *
 * Tentamos ativar assim que a página carrega.
 */

window.addEventListener(
    "load",
    function() {

        setTimeout(
            function() {

                ativarEscutaPalma();

            },
            700
        );

    }
);


/* =====================================================
   CLIQUE NO STATUS
===================================================== */

if (wakeStatus) {

    wakeStatus.addEventListener(
        "click",
        function() {

            if (!wakeListening) {

                ativarEscutaPalma();

            }

        }
    );

}


/* =====================================================
   BOTÃO DE MICROFONE
===================================================== */

if (voiceButton) {

    voiceButton.addEventListener(
        "click",
        function() {

            if (!nexAwake) {

                adicionarMensagem(
                    "Primeiro bata palma para me acordar. 👏",
                    "nex"
                );

                return;

            }


            if (falando) {
                return;
            }


            iniciarReconhecimento();

        }
    );

}


/* =====================================================
   FECHAR CELULAR
===================================================== */

if (closePhone) {

    closePhone.addEventListener(
        "click",
        function() {

            if (phone) {

                phone.style.display =
                    "none";

            }

        }
    );

}


/* =====================================================
   RELÓGIO
===================================================== */

function atualizarHora() {

    const agora =
        new Date();


    const horas =
        String(
            agora.getHours()
        ).padStart(
            2,
            "0"
        );


    const minutos =
        String(
            agora.getMinutes()
        ).padStart(
            2,
            "0"
        );


    const phoneTime =
        document.getElementById(
            "phoneTime"
        );


    if (phoneTime) {

        phoneTime.textContent =
            `${horas}:${minutos}`;

    }

}


atualizarHora();


setInterval(
    atualizarHora,
    1000
);


/* =====================================================
   INICIALIZAÇÃO
===================================================== */

console.log(
    "🤖 Nex iniciado."
);

console.log(
    "💤 Estado: dormindo."
);

console.log(
    "👏 Aguardando palma..."
);