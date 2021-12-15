/**
 * this script wraps dropzone.js library for inputs of
 * ILIAS\UI\Component\Input\Field\File.
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */

// global dropzone.js setting
Dropzone.autoDiscover = false;

var il = il || {};
il.UI = il.UI || {};
il.UI.Input = il.UI.Input || {};
(function ($, Input) {
	Input.File = (function ($) {
		const SELECTOR = {
			dropzone: '.ui-input-file-input-dropzone',
			file_input: '.ui-input-file',
			file_entry: '.ui-input-file-input',
			file_list: '.ui-input-file-input-list',
			file_metadata: '.ui-input-file-metadata',
			file_id_input: 'input[type="hidden"]',
			file_error_msg: '.ui-input-file-input-error-msg',
			removal_glyph: '.glyph[aria-label="Close"]',
			expand_glyph: '.glyph[aria-label="Expand Content"]',
			collapse_glyph: '.glyph[aria-label="Collapse Content"]',
			form_submit_buttons: '.il-standard-form-cmd > button',
		};

		/**
		 * @type {boolean}
		 */
		let instantiated = false;

		/**
		 * @type {number}
		 */
		let current_dropzone_count = 0;

		/**
		 * @type {number}
		 */
		let current_dropzone = 0;

		/**
		 * @type {jQuery|{}}
		 */
		let current_form = {};

		/**
		 * @type {Dropzone[]}
		 */
		let dropzones = [];

		/**
		 * @param {string} input_id
		 * @param {string} upload_url
		 * @param {string} removal_url
		 * @param {string} file_identifier
		 * @param {int} max_file_amount
		 * @param {int} max_file_size
		 * @param {string[]} mime_types
		 * @param {boolean} is_disabled
		 */
		let init = function (
			input_id,
			upload_url,
			removal_url,
			file_identifier,
			max_file_amount,
			max_file_size,
			mime_types,
			is_disabled,
		) {
			if (typeof dropzones[input_id] !== 'undefined') {
				console.error(`Error: tried to register input '${input_id}' as file input twice.`);
				return;
			}

			let file_list = document.querySelector(`#${input_id} ${SELECTOR.file_list}`);
			let action_button = document.querySelector(`#${input_id} ${SELECTOR.dropzone} button`);
			if (is_disabled) {
				action_button.is_disabled = true;
				return;
			}

			dropzones[input_id] = new Dropzone(`#${input_id} ${SELECTOR.dropzone}`, {
				url: encodeURI(upload_url),
				uploadMultiple: (1 < max_file_amount),
				acceptedFiles: (0 < mime_types.length) ? mime_types : null,
				maxFiles: max_file_amount,
				maxFileSize: max_file_size,
				previewsContainer: file_list,
				clickable: action_button,
				autoProcessQueue: false,
				parallelUploads: 1,
				file_identifier: file_identifier,
				removal_url: removal_url,
				input_id: input_id,

				// override default rendering function
				addedfile: file => {
					addFileHook(file, input_id);
				},
			});

			initDropzoneEventListeners(dropzones[input_id]);
			setupExpansionGlyphs();

			if (!instantiated) {
				initGlobalFileEventListeners();
				instantiated = true;
			}
		}

		/**
		 * @param {Dropzone} dropzone
		 */
		let initDropzoneEventListeners = function (dropzone) {
			dropzone.on('queuecomplete', submitCurrentFormHook);
			dropzone.on('processing', enableAutoProcessingHook);
			dropzone.on('success', setFilePreviewFileId);
		}

		/**
		 * @param {File}   file
		 * @param {string} json_response
		 */
		let setFilePreviewFileId = function (file, json_response) {
			let response = Object.assign(JSON.parse(json_response));
			let file_id_input = $(`#${file.input_id}`);
			let file_preview = file_id_input.closest(SELECTOR.file_entry);
			let dropzone = dropzones[file_id_input.closest(SELECTOR.file_input).attr('id')];

			if (typeof response.status === 'undefined' || 1 !== response.status) {
				response.responseText = response.message;
				ajaxResponseFailureHook(response, file_preview);
				return;
			}

			// set the upload results IRSS file id.
			file_id_input.val(response[dropzone.options.file_identifier]);
		}

		/**
		 * @param {Event} event
		 */
		let removeFileManuallyHook = function (event) {
			let removal_glyph = $(this);
			let file_input_id = removal_glyph.closest(SELECTOR.file_list).parent().attr('id');

			// abort if the file input was not yet initialized properly.
			if (typeof dropzones[file_input_id] === 'undefined') {
				console.error(`Error: tried to remove file from uninitialized input: '${file_input_id}'`);
				return;
			}

			let file_preview = removal_glyph.parent();
			let hidden_file_input = file_preview.find(SELECTOR.file_id_input);
			let dropzone_options = dropzones[file_input_id].options;

			// only remove files that have the removable class and were
			// already stored on the server.
			if ('' === hidden_file_input.val()) {
				return;
			}

			// stop event propagation as there may occurs an error.
			event.stopImmediatePropagation();

			// disable the removal button, by changing the aria-label
			// the global event listener won't trigger this hook again.
			removal_glyph.attr('aria-label', 'Close Disabled');
			removal_glyph.css('color', 'grey');

			$.ajax({
				type: 'GET',
				url: dropzone_options.removal_url,
				data: {
					[dropzone_options.file_identifier]: hidden_file_input.val(),
				},
				success: json_response => {
					$(this).parent().remove();
					ajaxResponseSuccessHook(json_response, file_preview);
				},
				error: json_response => {
					ajaxResponseFailureHook(json_response, file_preview);
				},
			});
		}

		/**
		 * @param {File} file
		 * @param {string} input_id
		 */
		let addFileHook = function (file, input_id) {
			let preview = il.UI.Input.DynamicInputsRenderer.render(input_id);
			console.log(preview);
			if (null === preview) {
				console.error(`Error: could not append preview for newly added file: ${file}`);
				return;
			}

			// store rendered preview id temporarily in file, to retrieve
			// the corresponding input later.
			file.input_id = preview.find(SELECTOR.file_id_input).attr('id');

			// add file info to preview and setup expansion toggles.
			preview.find('[data-dz-name]').text(file.name);
			preview.find('[data-dz-size]').text(beautifyFileSize(file.size));
			setupExpansionGlyphs(preview);
		}

		/**
		 * @param {string} json_response
		 * @param {jQuery} file_preview
		 */
		let ajaxResponseSuccessHook = function (json_response, file_preview) {
			let response = Object.assign(JSON.parse(json_response));

			// if the delivered response status is not 1 an
			// error occurred and the failure hook is fired.
			if (typeof response.status === 'undefined' || 1 !== response.status) {
				displayPreviewErrorMessage(response.message, file_preview);
			}
		}

		/**
		 * @param {jQuery.jqXHR} response
		 * @param {jQuery} file_preview
		 */
		let ajaxResponseFailureHook = function (response, file_preview) {
			console.error(response.status, response.responseText);
			displayPreviewErrorMessage(
				'An error occurred, check the console for more information.',
				file_preview
			);
		}

		let enableAutoProcessingHook = function () {
			let dropzone = $(this)[0];

			// if there are more than one file in the current
			// dropzone's queue, the auto-processing can be
			// enabled after the first file was processed.
			if (1 !== dropzone.files.length) {
				dropzone.options.autoProcessQueue = true;
			}
		}

		let initGlobalFileEventListeners = function () {
			$(SELECTOR.dropzone)
			.closest('form')
			.on(
				'click',
				SELECTOR.form_submit_buttons,
				processFormSubmission
			);

			$(document).on(
				'click',
				`${SELECTOR.file_list} ${SELECTOR.removal_glyph}`,
				removeFileManuallyHook
			);

			$(document).on(
				'click',
				`${SELECTOR.file_list} ${SELECTOR.collapse_glyph}, ${SELECTOR.file_list} ${SELECTOR.expand_glyph}`,
				toggleExpansionGlyphsHook
			);
		}

		/**
		 * @param {Event} event
		 */
		let processFormSubmission = function (event) {
			current_form = $(this).closest('form');
			event.preventDefault();

			// disable ALL submit buttons on the current page,
			// so the data is submitted AFTER the queue is
			// processed (finishQueueHook is triggered).
			$(document)
			.find(SELECTOR.form_submit_buttons)
			.each(function () {
				$(this).attr('disabled', true);
			});

			processFormFileInputs(current_form);
		}

		/**
		 * @param {jQuery} form
		 */
		let processFormFileInputs = function (form) {
			// retrieve all file inputs of the current form.
			let file_inputs = current_form.find(SELECTOR.file_input);
			current_dropzone_count = file_inputs.length;

			// in case multiple file-inputs were added to ONE form, they
			// all need to be processed.
			if (Array.isArray(file_inputs)) {
				for (let i = 0; i < file_inputs.length; i++) {
					let dropzone = dropzones[file_inputs[i].attr('id')];
					dropzone.processQueue();
				}
			} else {
				let dropzone = dropzones[file_inputs.attr('id')];
				console.log(dropzone);

				if (0 !== dropzone.files.length) {
					dropzone.processQueue();
				} else {
					current_form.submit();
				}
			}
		}

		let submitCurrentFormHook = function () {
			// submit the current form only if all dropzones
			// were processed.
			if (++current_dropzone === current_dropzone_count) {
				current_form.submit();
			}
		}

		let toggleExpansionGlyphsHook = function () {
			let current_glyph = $(this);
			let other_glyph = ('Expand Content' === current_glyph.attr('aria-label')) ?
				current_glyph.parent().find(SELECTOR.collapse_glyph) :
				current_glyph.parent().find(SELECTOR.expand_glyph)
			;

			current_glyph.parent().find(SELECTOR.file_metadata).toggle();
			other_glyph.show();
			current_glyph.hide();
		}

		/**
		 * @param {jQuery|null} file_entry
		 */
		let setupExpansionGlyphs = function (file_entry = null) {
			if (null === file_entry) {
				$(`${SELECTOR.file_entry} ${SELECTOR.collapse_glyph}`).hide();
			} else {
				file_entry.find(SELECTOR.collapse_glyph).hide();
			}
		}

		/**
		 * @param {string} message
		 * @param {jQuery} file_preview
		 */
		let displayPreviewErrorMessage = function (message, file_preview) {
			file_preview.find(SELECTOR.file_error_msg).text(message);
		}

		/**
		 * @param {int} bytes
		 * @return {string}
		 */
		let beautifyFileSize = function (bytes) {
			return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
		}

		return {
			addFile: addFileHook,
			init: init,
		}
	})($)
})($, il.UI.Input);