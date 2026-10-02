/* =====================================================
   NEX JARVIS
===================================================== */

(function () {

    "use strict";


    /*
    =====================================================
    CONFIGURAÇÃO
    =====================================================
    */

    const NEX_API =
        "/grapedev/nex/api/nex.php";

    const ACTION_API =
        "/grapedev/nex/api/action.php";


    /*
    =====================================================
    CRIAR INTERFACE
    =====================================================
    */

    const launcher =
        document.createElement("button");

    launcher.id =
        "nexLauncher";

    launcher.innerHTML =
        "✦";


    const nex =
        document.createElement("div");

    nex.id =
        "nexJarvis";

    nex.classList.add(
        "nex-hidden"
    );


    nex.innerHTML = `

        <div id="nexHeader">

            <div id="nexAvatar">
                ✦
            </div>

            <div>

                <div id="nexName">
                    Nex
                </div>

                <div id="nexStatus">
                    ● SISTEMA ONLINE
                </div>

            </div>

        </div>


        <div id="nexMessages">

            <div class="nex-message nex">

                Olá. Eu sou o Nex,
                assistente virtual da GrapeDev.

                <br><br>

                Como posso ajudar?

            </div>

        </div>


        <div id="nexInputArea">

            <input
                id="nexInput"
                type="text"
                placeholder="Fale com o Nex..."
                autocomplete="off"
            >

            <button id="nexSend">
                ➤
            </button>

        </div>

    `;


    document.body.appendChild(
        launcher
    );

    document.body.appendChild(
        nex
    );


    /*
    =====================================================
    ELEMENTOS
    =====================================================
    */

    const input =
        document.getElementById(
            "nexInput"
        );

    const send =
        document.getElementById(
            "nexSend"
        );

    const messages =
        document.getElementById(
            "nexMessages"
        );


    /*
    =====================================================
    ABRIR / FECHAR
    =====================================================
    */

    launcher.onclick =
        function () {

            nex.classList.toggle(
                "nex-hidden"
            );

            launcher.style.display =
                nex.classList.contains(
                    "nex-hidden"
                )
                    ? "block"
                    : "none";

            if (
                !nex.classList.contains(
                    "nex-hidden"
                )
            ) {

                input.focus();

            }

        };


    /*
    =====================================================
    ADICIONAR MENSAGEM
    =====================================================
    */

    function adicionarMensagem(
        texto,
        tipo
    ) {

        const div =
            document.createElement(
                "div"
            );

        div.className =
            "nex-message " + tipo;

        div.textContent =
            texto;

        messages.appendChild(
            div
        );

        messages.scrollTop =
            messages.scrollHeight;

    }


    /*
    =====================================================
    EXECUTAR AÇÃO
    =====================================================
    */

    async function executarAcao(
        acao
    ) {

        try {

            const resposta =
                await fetch(
                    ACTION_API,
                    {

                        method: "POST",

                        headers: {
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify({
                                acao: acao
                            })

                    }
                );


            const dados =
                await resposta.json();


            if (
                !dados.sucesso
            ) {

                adicionarMensagem(
                    dados.mensagem ||
                    "Não consegui executar essa ação.",
                    "nex"
                );

                return;

            }


            adicionarMensagem(
                dados.mensagem,
                "nex"
            );


            if (
                dados.dados &&
                dados.dados.url
            ) {

                setTimeout(
                    function () {

                        window.location.href =
                            dados.dados.url;

                    },
                    500
                );

            }


        } catch (erro) {

            console.error(
                erro
            );

            adicionarMensagem(
                "Não consegui executar a ação.",
                "nex"
            );

        }

    }


    /*
    =====================================================
    ENVIAR
    =====================================================
    */

    async function enviar() {

        const texto =
            input.value.trim();


        if (!texto)
            return;


        adicionarMensagem(
            texto,
            "user"
        );


        input.value =
            "";


        adicionarMensagem(
            "Analisando...",
            "nex"
        );


        try {

            const resposta =
                await fetch(
                    NEX_API,
                    {

                        method: "POST",

                        headers: {

                            "Content-Type":
                                "application/json"

                        },

                        body:
                            JSON.stringify({

                                mensagem:
                                    texto

                            })

                    }
                );


            const dados =
                await resposta.json();


            /*
            =============================================
            REMOVER "ANALISANDO..."
            =============================================
            */

            const ultimas =
                messages.querySelectorAll(
                    ".nex-message"
                );

            const ultima =
                ultimas[
                    ultimas.length - 1
                ];


            if (
                ultima &&
                ultima.textContent ===
                    "Analisando..."
            ) {

                ultima.remove();

            }


            /*
            =============================================
            RESPOSTA
            =============================================
            */

            if (
                !dados.sucesso
            ) {

                adicionarMensagem(
                    dados.erro ||
                    "Ocorreu um erro.",
                    "nex"
                );

                return;

            }


            adicionarMensagem(
                dados.resposta,
                "nex"
            );


            /*
            =============================================
            AÇÃO
            =============================================
            */

            if (
                dados.acao
            ) {

                await executarAcao(
                    dados.acao
                );

            }


        } catch (erro) {

            console.error(
                erro
            );


            const ultimas =
                messages.querySelectorAll(
                    ".nex-message"
                );

            const ultima =
                ultimas[
                    ultimas.length - 1
                ];


            if (
                ultima &&
                ultima.textContent ===
                    "Analisando..."
            ) {

                ultima.remove();

            }


            adicionarMensagem(
                "Não consegui me conectar ao cérebro do Nex.",
                "nex"
            );

        }

    }


    /*
    =====================================================
    EVENTOS
    =====================================================
    */

    send.onclick =
        enviar;


    input.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Enter"
            ) {

                enviar();

            }

        }
    );


})();