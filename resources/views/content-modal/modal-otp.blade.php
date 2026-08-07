<div>
    <input type="password" maxlength="1" class="otp-input input">
    <input type="password" maxlength="1" class="otp-input input" disabled>
    <input type="password" maxlength="1" class="otp-input input" disabled>
    <input type="password" maxlength="1" class="otp-input input" disabled>
    <input type="password" maxlength="1" class="otp-input input" disabled>
</div>

<script>
    document.querySelectorAll(".input").forEach((input, index) => {
        input.addEventListener("input", () => {
            // kalau user isi sesuatu
            if (input.value) {
                let allInputs = document.querySelectorAll(".input");
                if (allInputs[index + 1]) {
                    allInputs[index + 1].disabled = false;
                    allInputs[index + 1].focus();
                } else {
                    // kotak terakhir terisi — otomatis proses login
                    setTimeout(() => {
                        document.querySelector("#prossess")?.click();
                    }, 150);
                }
            } else {
                // kalau dikosongkan, disable semua setelahnya
                for (let i = index + 1; i < document.querySelectorAll(".input").length; i++) {
                    document.querySelectorAll(".input")[i].value = "";
                    document.querySelectorAll(".input")[i].disabled = true;
                }
            }
        });

        // kalau tekan backspace dan input kosong, balik ke sebelumnya
        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !input.value && document.querySelectorAll(".input")[index - 1]) {
                document.querySelectorAll(".input")[index - 1].focus();
            }
        });
    });

</script>
