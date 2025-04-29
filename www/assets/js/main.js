class SpinnerExtension {
    initialize(naja)
    {
        const mainContent = document.querySelector('body');
        const spinner = document.querySelector('.spinner');
        naja.addEventListener('start', function () {
            mainContent.classList.add('opacity-50');
            spinner.classList.remove('d-none');
            spinner.classList.add('d-inline-block');
        });
        naja.addEventListener('complete', function () {
            mainContent.classList.remove('opacity-50');
            spinner.classList.add('d-none');
            spinner.classList.remove('d-inline-block');
        });
    }
}