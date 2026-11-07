"use strict";

/* =========================
   ELEMENTS
========================= */

const form = document.getElementById("check-form");

const urlInput = document.getElementById("website-url");

const checkButton = document.getElementById("check-button");

const buttonText = document.querySelector(".button-text");

const buttonArrow = document.querySelector(".button-arrow");

const errorMessage = document.getElementById("error-message");

const resultSection = document.getElementById("result-section");

const resultIcon = document.getElementById("result-icon");

const resultStatus = document.getElementById("result-status");

const resultUrl = document.getElementById("result-url");

const resultBadge = document.getElementById("result-badge");

const resultHttp = document.getElementById("result-http");

const resultHttpDescription = document.getElementById(
    "result-http-description"
);

const resultTime = document.getElementById("result-time");

const resultMessage = document.getElementById("result-message");

/* =========================
   RESULT DISPLAY
========================= */

/**
 * Get a human-readable HTTP status.
 */
function getStatusDescription(status) {
    const descriptions = {
        0: "No response",
        200: "OK",
        201: "Created",
        204: "No Content",
        301: "Moved Permanently",
        302: "Found",
        304: "Not Modified",
        400: "Bad Request",
        401: "Unauthorized",
        403: "Forbidden",
        404: "Not Found",
        405: "Method Not Allowed",
        429: "Too Many Requests",
        500: "Internal Server Error",
        502: "Bad Gateway",
        503: "Service Unavailable",
        504: "Gateway Timeout"
    };

    return descriptions[status] || "HTTP Response";
}


/**
 * Display a website check result.
 */
function displayResult(data) {
    resultUrl.textContent = data.url;

    resultHttp.textContent = data.status;

    resultHttpDescription.textContent =
        getStatusDescription(data.status);

    resultTime.textContent = `${data.responseTime} ms`;

    resultMessage.textContent = data.message;

    if (data.online) {
        resultStatus.textContent = "Website is responding";

        resultBadge.textContent = "● Online";

        resultBadge.className = "status-badge";

        resultIcon.textContent = "✓";

        resultIcon.className = "result-icon";

        resultHttp.style.color = "var(--success)";

    } else {
        resultStatus.textContent = "Website is not responding";

        resultBadge.textContent = "● Offline";

        resultBadge.className = "status-badge offline";

        resultIcon.textContent = "×";

        resultIcon.className = "result-icon offline";

        resultHttp.style.color = "var(--danger)";
    }

    resultSection.classList.remove("hidden");
}


/* =========================
   ERROR HANDLING
========================= */

/**
 * Display an error message.
 */
function showError(message) {
    errorMessage.textContent = message;

    errorMessage.classList.remove("hidden");
}


/**
 * Hide the error message.
 */
function hideError() {
    errorMessage.textContent = "";

    errorMessage.classList.add("hidden");
}


/* =========================
   LOADING STATE
========================= */

/**
 * Update the button while checking.
 */
function setLoading(loading) {
    checkButton.disabled = loading;

    if (loading) {
        buttonText.textContent = "Checking...";

        buttonArrow.textContent = "↻";

    } else {
        buttonText.textContent = "Check website";

        buttonArrow.textContent = "→";
    }
}


/* =========================
   FORM SUBMISSION
========================= */


form.addEventListener("submit", async (event) => {
    event.preventDefault();

    hideError();
    resultSection.classList.add("hidden");

    const url = urlInput.value.trim();

    if (!url) {
        showError("Please enter a website URL.");
        return;
    }

    setLoading(true);

    try {
        const response = await fetch("check.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({ url })
        });

        // Read as text first so an empty response doesn't
        // cause an Unexpected end of JSON input error.
        const responseText = await response.text();

        if (!responseText.trim()) {
            throw new Error(
                `The PHP server returned an empty response (HTTP ${response.status}). Check your PHP error log.`
            );
        }

        let result;

        try {
            result = JSON.parse(responseText);
        } catch {
            console.error("Invalid server response:", responseText);

            throw new Error(
                "The server returned invalid JSON. Check check.php for PHP errors."
            );
        }

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Could not check the website."
            );
        }

        displayResult(result.data);

    } catch (error) {
        console.error("PulseCheck error:", error);

        showError(error.message || "Something went wrong.");

    } finally {
        setLoading(false);
    }
});

