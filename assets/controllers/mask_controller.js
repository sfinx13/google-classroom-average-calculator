import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["container"];

    toggle(event) {
        const isMasked = event.currentTarget.checked;
        if (isMasked) {
            this.containerTarget.classList.add('is-masked');
            event.currentTarget.setAttribute('aria-checked', 'true');
        } else {
            this.containerTarget.classList.remove('is-masked');
            event.currentTarget.setAttribute('aria-checked', 'false');
        }
    }
}
