"use strict";

/**
 * Handles the company deployment response.
 * @param {Object} data - The response data.
 * @todo Add a pooling handling to update the user with current stage of deployment.
 */
function handleCompanyDeployment(data) {
	if (data?.total_success > 0) {
		setTimeout(function () {
			try {
				window.parent.location.reload();
			} catch (error) {
				console.log(error);
			}
			window.location.reload();
		}, 1000);
	}
	if (data.errors?.length) {
		data.errors.forEach(function (error) {
			alert_float("danger", error, 10000);
		});

		$(".company-status .fa-spin").removeClass("fa-spin");

		setTimeout(function () {
			try {
				window.parent.location.reload();
			} catch (error) {
				console.log(error);
			}
			window.location.reload();
		}, 8000);
	}
}

/**
 * Removes submenu items from the DOM.
 * It removes some menu/nav from the client side.
 */
function removeSubmenuItems() {
	let selectors =
		".section-client-dashboard>dl:first-of-type, .projects-summary-heading,.submenu.customer-top-submenu";
	document.querySelectorAll(selectors).forEach(function ($element) {
		$element.remove();
	});
	$(selectors)?.remove();
}

/**
 * Handles the company modal view.
 */
function handleCompanyModalView() {
	let slug = $(this).data("slug");

	let viewPane = $("#view-company-modal");
	if (viewPane.hasClass("modal")) viewPane.modal("show");
	else {
		viewPane.slideDown();
		viewPane.find(".close,.close-btn").click(function () {
			viewPane.slideUp();
		});
	}

	$('select[name="view-company"]')
		.selectpicker("val", slug)
		.trigger("change");

	try {
		let iframe = getCompanyViewerFrame();
		iframe.contentWindow.set_body_small();
	} catch (error) {
		console.log(error);
	}
}

/**
 * Handles the modal company change event.
 */
function handleModalCompanyChange() {
	let slug = $(this).val();
	if (!slug.length) $("#view-company-modal").modal("hide");
	magicAuth(slug);
}

/**
 * Get the company preview iframe
 * @returns object
 */
function getCompanyViewerFrame() {
	return document.querySelector("#company-viewer");
}

/**
 * Loads a company into the modal viewer.
 * @param {string} slug - The company slug.
 */
function magicAuth(slug) {
	let iframe = getCompanyViewerFrame();
	iframe.src = PERFEX_SAAS_MAGIC_AUTH_BASE_URL + slug;
	iframe.onload = function () {
		$(".first-loader").hide();
	};
	iframe.contentWindow?.NProgress?.start() || $(".first-loader").show();
}

/*
 * Debounce function to limit the frequency of function execution
 * @param {Function} func - The function to be debounced
 * @param {number} wait - The debounce wait time in milliseconds
 * @param {boolean} immediate - Whether to execute the function immediately
 * @returns {Function} - The debounced function
 */
function debounce(func, wait, immediate) {
	var timeout;
	return function () {
		var context = this,
			args = arguments;
		var later = function () {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
}

function slugifyTenantId(text) {
	return text
		.trim()
		.toLowerCase()
		.split(" ")[0]
		.substring(0, PERFEX_SAAS_MAX_SLUG_LENGTH)
		.replace(/[^a-z0-9]+/g, "-");
}
/*
 * Function to generate slug and check its availability
 */
function generateSlugAndCheckAvailability() {
	// Generate the slug from the input value
	let slug = slugifyTenantId($("input[name=slug]").val());

	let $statusLabel = $("#slug-check-label");

	if (!slug.length || !slug.replaceAll("-", "").length) {
		$statusLabel.html("");
		return;
	}

	let domain = slug + "." + PERFEX_SAAS_DEFAULT_HOST;

	// Set the generated slug as the input value
	$("input[name=slug]").val(slug);

	// Display a message indicating that availability is being checked
	$statusLabel.html("<i class='fa fa-spinner fa-spin tw-mr-1'></i>" + domain);

	const handleCheckResult = (data) => {
		let isAvailable = data?.available;

		// Update the label with the slug availability status
		$statusLabel.html(
			`<span class='text-${
				isAvailable ? "success" : "danger"
			}'>${domain}</span>`
		);
	};

	// Send an AJAX request to check the slug availability on the server
	$.getJSON(
		`${site_url}${PERFEX_SAAS_MODULE_NAME}/api/is_slug_available/${slug}`,
		handleCheckResult
	).fail((error, status, statusText) => {
		alert_float("danger", statusText, 5000);
		handleCheckResult({});
	});
}

/**
 * Function to bind and listen to the slug input field
 *
 * @param {string} formSelector The parent element selector for the inputs
 * @param {string} slugSourceInputSelector Optional element that should be used for auto generating the slug
 * @returns
 */
function bindAndListenToSlugInput(
	formSelector = "#add-company-form",
	slugSourceInputSelector = "#add-company-form input[name='name']"
) {
	if (!$(formSelector).length) {
		console.warn("provided select not exist:", formSelector);
		return;
	}

	let slugInputSelector = "input[name=slug]";

	// Inject the result placeholder HTML
	$(
		'<small id="slug-check-label" class="text-right tw-w-full tw-block tw-text-xs"></small>'
	).insertAfter(slugInputSelector);

	// Debounced event handler for company name input changes
	let debouncedGenerateSlugAndCheckAvailability = debounce(
		generateSlugAndCheckAvailability,
		500
	);

	// Generate slug from company name input
	if ($(slugSourceInputSelector).length)
		$(slugSourceInputSelector)
			.unbind("input")
			.on("input", function () {
				var companyName = $(slugSourceInputSelector).val();
				var slug = slugifyTenantId(companyName);
				$(slugInputSelector).val(slug).trigger("input");
			});

	// Check for availability of the slug
	$(formSelector + " " + slugInputSelector)
		.unbind("input")
		.on("input", debouncedGenerateSlugAndCheckAvailability);
}

$(document).ready(function () {
	$(".ps-container").insertAfter("#greeting");

	// Remove submenu (e.g., calendar and files)
	if (PERFEX_SAAS_CONTROL_CLIENT_MENU) removeSubmenuItems();

	// Hide the form initially
	$("#add-company-form").hide();

	// Show the form when the add button is clicked
	$(".add-company-btn").click(function () {
		$("#add-company-trigger").slideUp();
		$("#add-company-form").slideDown();
	});

	// Cancel button closes the form and shows the early UI
	$("#cancel-add-company").click(function () {
		$("#add-company-form").slideUp();
		$("#add-company-trigger").slideDown();
	});

	// Show the edit form
	$(".company .dropdown-menu .edit-company-nav").click(function () {
		let $company = $(this).parents(".company");
		$company.find(".panel_footer, .info, .dropdown").slideUp();
		$company.find(".edit-form").slideDown();
		$company.find(".bootstrap-select").slideDown();
	});

	// Cancel button closes the edit form and shows the early UI
	$(".company .edit-form .btn[type='button']").click(function () {
		let $company = $(this).parents(".company");
		$company.find(".edit-form").slideUp();
		$company.find(".info, .panel_footer, .dropdown").slideDown();
	});

	// Render Saas view
	let view = PERFEX_SAAS_ACTIVE_SEGMENT;
	if (view) {
		$(".ps-view").hide();
		showSaasView(view);
	}

	// Function to show the specified Saas view
	function showSaasView(view) {
		$(view.replace("?", "#")).show();

		if (
			window.location.href.includes(view) ||
			window.location.pathname.replaceAll("/", "") == "clients"
		)
			$(".customers-nav-item-" + view.replace("?", "")).addClass(
				"active"
			);
	}

	// Worker helper for instant deployment of a company
	$.getJSON(site_url + "clients/companies/deploy", handleCompanyDeployment);

	// Company modal view
	$(".view-company").click(handleCompanyModalView);

	// Detect change in modal company list selector and react
	$(document).on("change", '[name="view-company"]', handleModalCompanyChange);

	// Click the first company by default if client is having only one.
	setTimeout(() => {
		let companyList = $("#companies:visible .company.active");
		if (
			companyList.length === 1 &&
			sessionStorage.getItem("autolaunched") !== "1"
		) {
			sessionStorage.setItem("autolaunched", "1");
			$(companyList[0]).find(".view-company").click();
		}
	}, 500);

	/** Subdomain checking for improved UX */
	bindAndListenToSlugInput();

	if (window.location.search.startsWith("?request_custom_module")) {
		let searchParams = new URLSearchParams(window.location.search);
		$("[name=subject]").val(searchParams.get("title"));
		$("[name=message]").val(searchParams.get("message"));
	}
});
