<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta
        name="description"
        content="Check website availability, HTTP status codes, and response times."
    >
    <title>PulseCheck - Website Monitor</title>
    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
    <script
        src="assets/js/script.js"
        defer
    ></script>
</head>

<body>

    <header class="navbar">

        <a href="index.php" class="logo">
            <span class="logo-icon">P</span>
            pulse<span class="logo-light">check</span>
        </a>

        <div class="nav-right">
            <span class="nav-indicator"></span>
            Website Monitoring
        </div>

    </header>


    <main class="container">

        <section class="hero">

            <div class="hero-label">
                WEB DEVELOPMENT PROJECT
            </div>

            <h1>
                Is your website<br>
                <span>online?</span>
            </h1>

            <p>
                A small tool I made to check whether a website is up
                and how quickly it responds.
            </p>

        </section>


        <section class="monitor-card">

            <div class="card-heading">

                <div>
                    <h2>Check a website</h2>

                    <p>
                        Enter a URL to check its status.
                    </p>
                </div>

                <div class="card-icon">
                    <svg
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/>
                        <path d="M12 2a15.3 15.3 0 0 0-4 10 15.3 15.3 0 0 0 4 10"/>
                    </svg>
                </div>

            </div>


            <form id="check-form">

                <label
                    for="website-url"
                    class="input-label"
                >
                    Website URL
                </label>

                <div class="input-group">

                    <div class="input-wrapper">

                        <span class="input-prefix">
                            <svg
                                width="19"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M2 12h20"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/>
                                <path d="M12 2a15.3 15.3 0 0 0-4 10 15.3 15.3 0 0 0 4 10"/>
                            </svg>
                        </span>

                        <input
                            type="url"
                            id="website-url"
                            name="url"
                            placeholder="https://example.com"
                            autocomplete="url"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        id="check-button"
                        class="check-button"
                    >
                        <span class="button-text">
                            Check website
                        </span>

                        <span class="button-arrow">
                            →
                        </span>
                    </button>

                </div>

                <p class="input-hint">
                    Supports public websites using HTTP or HTTPS.
                </p>

            </form>


            <div
                id="error-message"
                class="error-message hidden"
                role="alert"
            ></div>


            <section
                id="result-section"
                class="result-section hidden"
                aria-live="polite"
            >

                <div class="result-header">

                    <div class="result-title">

                        <div
                            id="result-icon"
                            class="result-icon"
                        ></div>

                        <div>
                            <h3 id="result-status"></h3>

                            <p id="result-url"></p>
                        </div>

                    </div>

                    <span
                        id="result-badge"
                        class="status-badge"
                    ></span>

                </div>


                <div class="result-stats">

                    <div class="stat-card">

                        <div class="stat-label">
                            HTTP STATUS
                        </div>

                        <div
                            id="result-http"
                            class="stat-value"
                        ></div>

                        <div
                            id="result-http-description"
                            class="stat-description"
                        ></div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-label">
                            RESPONSE TIME
                        </div>

                        <div
                            id="result-time"
                            class="stat-value"
                        ></div>

                        <div class="stat-description">
                            Request duration
                        </div>

                    </div>

                </div>


                <div
                    id="result-message"
                    class="result-message"
                ></div>

            </section>

        </section>


    </main>


    <footer class="footer">

        <div class="footer-logo">
            pulse<span>check</span>
        </div>

        <p>
            A small web development project.
        </p>

        <p class="footer-copyright">
            <?= date('Y') ?> PulseCheck
        </p>

    </footer>

</body>

</html>