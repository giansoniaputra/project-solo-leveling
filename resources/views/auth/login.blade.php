<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="/assets-front-end/style.css">
    <title>Document</title>
    <style>
        .otp-container {
            background-color: #111827;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            color: white;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-input {
            width: 40px;
            height: 50px;
            margin: 0 5px;
            text-align: center;
            font-size: 20px;
            border: 1px solid #444;
            border-radius: 5px;
            background: #000;
            color: white;
        }

        .otp-input:focus {
            border-color: #3b82f6;
            outline: none;
        }

        .verify-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3b82f6;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .verify-btn:hover {
            background-color: #2563eb;
        }

    </style>
</head>
<body>
    <button class="cyber-btn" id="btn-login" popovertarget="upgrade" popovertargetaction="show" aria-label="Upgrade" data-action="Upgrade">
        <span class="backdrop">
            <span class="corner"></span>
        </span>
        <kbd>U</kbd>
        <span>Login</span>
        <div class="glitch" aria-hidden="true">
            <span class="backdrop">
                <span class="corner"></span>
            </span>
            <kbd>U</kbd>
            <span class="letters">
                <span>U</span>
                <span>p</span>
                <span>g</span>
                <span>r</span>
                <span>a</span>
                <span>d</span>
                <span>e</span>
            </span>
        </div>
    </button>
    @include('modal')
    <a aria-label="Follow Jhey" class="bear-link" href="https://twitter.com/intent/follow?screen_name=jh3yy" target="_blank" rel="noreferrer noopener">
        <svg class="w-9" viewBox="0 0 969 955" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="161.191" cy="320.191" r="133.191" stroke="currentColor" stroke-width="20"></circle>
            <circle cx="806.809" cy="320.191" r="133.191" stroke="currentColor" stroke-width="20"></circle>
            <circle cx="695.019" cy="587.733" r="31.4016" fill="currentColor"></circle>
            <circle cx="272.981" cy="587.733" r="31.4016" fill="currentColor"></circle>
            <path d="M564.388 712.083C564.388 743.994 526.035 779.911 483.372 779.911C440.709 779.911 402.356 743.994 402.356 712.083C402.356 680.173 440.709 664.353 483.372 664.353C526.035 664.353 564.388 680.173 564.388 712.083Z" fill="currentColor"></path>
            <rect x="310.42" y="448.31" width="343.468" height="51.4986" fill="#FF1E1E"></rect>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M745.643 288.24C815.368 344.185 854.539 432.623 854.539 511.741H614.938V454.652C614.938 433.113 597.477 415.652 575.938 415.652H388.37C366.831 415.652 349.37 433.113 349.37 454.652V511.741L110.949 511.741C110.949 432.623 150.12 344.185 219.845 288.24C289.57 232.295 384.138 200.865 482.744 200.865C581.35 200.865 675.918 232.295 745.643 288.24Z" fill="currentColor"></path>
        </svg>
    </a>
    <script type="module" src="/assets-front-end/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        let renderModal = () => {
            $.ajax({
                url: "/render-modal-otp"
                , type: "GET"
                , dataType: 'json'
                , success: function(response) {
                    $("#modal-body").html(response.data[0])
                    $("#modal-body-glitch").html(response.data[1])
                }
            });
        }
        renderModal()
        $("#btn-login").on('click', function() {
            renderModal()
        })

        document.querySelector("#prossess").addEventListener('click', function() {
            const popover = document.querySelector('.modal')
            let bodyy = document.querySelector('#modal-body')
            let bodyG = document.querySelector('#modal-body-glitch')
            let input = document.querySelectorAll("#modal-body .input");
            let token = document.querySelector("input[name='token']").value;
            let angka = '';
            input.forEach((a) => {
                angka += a.value;
            })
            $.ajax({
                data: {
                    name: angka
                    , password: angka
                    , _token: token
                }
                , url: "/login"
                , type: "POST"
                , dataType: 'json'
                , success: function(response) {
                    setTimeout(() => {
                        popover.showPopover()
                        bodyy.innerHTML =
                            `
                        <div class="mb-3" style="margin:10px 0">
                            <label for="" class="form-label">Selemat Datang Player.</label>
                            </div>
                            </div>

                            `
                        bodyG.innerHTML =
                            `
                            <div class="mb-3" style="margin:10px 0">
                                <label for="" class="form-label">Selemat Datang Player.</label>
                                </div>
                                </div>`
                    }, 500)
                    setTimeout(() => {
                        document.location.href = "/"
                    }, 3000)
                }
                , error: function(response) {
                    console.log(response.responseJSON.errors);

                    setTimeout(() => {
                        popover.showPopover()
                        bodyy.innerHTML =
                            `
                <div class="mb-3" style="margin:10px 0">
                    <label for="" class="form-label">${(response.responseJSON.errors != undefined) ? response.responseJSON.errors.name:response.responseJSON.message }</label>
                    </div>
                </div>

                `
                        bodyG.innerHTML =
                            `
                <div class="mb-3" style="margin:10px 0">
                    <label for="" class="form-label">${(response.responseJSON.errors != undefined) ? response.responseJSON.errors.name:response.responseJSON.message }</label>
                    </div>
                </div>`
                    }, 500);
                }
            });

        })

    </script>
</body>
</html>
