(function () {
    'use strict';

    function showCrudFormsInModals() {
        document.querySelectorAll('form[data-admin-modal]').forEach(function (form, index) {
            if (form.closest('.modal')) {
                return;
            }

            var card = form.closest('.card');
            var target = card && card.contains(form) && !form.contains(card) && card.querySelectorAll('form').length === 1
                ? card
                : form;
            var title = form.getAttribute('data-modal-title') || 'Edit details';
            var modalId = 'adminCrudModal' + index;
            var trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'btn btn-primary admin-crud-trigger';
            trigger.setAttribute('data-toggle', 'modal');
            trigger.setAttribute('data-target', '#' + modalId);
            trigger.innerHTML = '<i class="material-icons">edit</i> ' + (form.getAttribute('data-modal-trigger') || title);

            var modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = modalId;
            modal.tabIndex = -1;
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-labelledby', modalId + 'Title');
            modal.setAttribute('aria-hidden', 'true');
            modal.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">' +
                '<div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="' + modalId + 'Title"></h5>' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' +
                '<div class="modal-body"></div></div></div>';
            modal.querySelector('.modal-title').textContent = title;

            target.parentNode.replaceChild(trigger, target);
            modal.querySelector('.modal-body').appendChild(target);
            document.body.appendChild(modal);
        });
    }

    function setupDeleteConfirmation() {
        var modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'adminDeleteConfirmation';
        modal.tabIndex = -1;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', 'adminDeleteConfirmationTitle');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = '<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title" id="adminDeleteConfirmationTitle">Confirm removal</h5>' +
            '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' +
            '<div class="modal-body"><p class="mb-0">Are you sure you want to continue with this removal?</p></div>' +
            '<div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>' +
            '<button type="button" class="btn btn-danger" data-delete-confirm-action>Continue</button></div></div></div>';
        document.body.appendChild(modal);

        var pendingLink = null;
        var pendingForm = null;
        var pendingSubmitter = null;
        document.addEventListener('click', function (event) {
            var link = event.target instanceof Element ? event.target.closest('a[href]') : null;
            if (!link || link.closest('.modal')) {
                return;
            }
            if (!link.matches('a[href*="action=delete"], a[onclick*="confirm("]')) {
                return;
            }

            event.preventDefault();
            var message = link.getAttribute('data-confirm-message') || 'Are you sure you want to continue with this removal?';
            modal.querySelector('.modal-body p').textContent = message;
            pendingLink = link.href;
            pendingForm = null;
            window.jQuery(modal).modal('show');
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            var submitter = event.submitter || document.activeElement;
            if (!(form instanceof HTMLFormElement) || form.closest('.modal')
                || !submitter || !submitter.name || !/(delete|remove)/i.test(submitter.name)
                || form.dataset.confirmed === '1') {
                if (form instanceof HTMLFormElement && form.dataset.confirmed === '1') {
                    delete form.dataset.confirmed;
                }
                return;
            }

            event.preventDefault();
            pendingForm = form;
            pendingSubmitter = submitter;
            pendingLink = null;
            modal.querySelector('.modal-body p').textContent = 'This action cannot be undone. Continue?';
            window.jQuery(modal).modal('show');
        });

        modal.querySelector('[data-delete-confirm-action]').addEventListener('click', function () {
            if (pendingLink) {
                window.location.href = pendingLink;
            } else if (pendingForm) {
                pendingForm.dataset.confirmed = '1';
                pendingForm.requestSubmit(pendingSubmitter);
            }
        });

        modal.addEventListener('hidden.bs.modal', function () {
            pendingLink = null;
            pendingForm = null;
            pendingSubmitter = null;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        showCrudFormsInModals();
        setupDeleteConfirmation();
    });
}());
