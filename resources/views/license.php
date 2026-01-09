<?php
/** @var Din9xtrCloud\ViewModels\LicenseViewModel $viewModel */
?>
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
        color: #2d3748;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1rem;
    }

    header {
        background: rgba(255, 255, 255, 0.98);
        color: #4a5568;
        padding: 1rem 2rem;
        width: 100%;
        text-align: center;
        font-weight: 700;
        font-size: 1.5rem;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        position: fixed;
        top: 0;
        z-index: 1000;
        border-radius: 0 0 20px 20px;
        margin: 0 auto;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
    }

    .navbar-brand {
        color: transparent;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        background-clip: text;
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .back-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .back-link:hover {
        color: #764ba2;
        transform: translateX(-3px);
    }

    main {
        width: 100%;
        max-width: 1000px;
        margin: 100px auto 60px;
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .license-container {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 24px;
        padding: 3rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08),
        0 8px 24px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .license-header {
        text-align: center;
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid rgba(0, 0, 0, 0.05);
    }

    .license-title {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }

    .license-subtitle {
        color: #718096;
        font-size: 1.1rem;
        line-height: 1.6;
        max-width: 800px;
        margin: 0 auto;
    }

    .quick-summary {
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        border-radius: 16px;
        padding: 2.5rem;
        margin-bottom: 3rem;
        border-left: 4px solid #667eea;
    }

    .summary-title {
        color: #2d3748;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .summary-title::before {
        content: '📋';
        font-size: 1.8rem;
    }

    .summary-content {
        color: #4a5568;
        line-height: 1.7;
        font-size: 1.05rem;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .permission-card {
        background: #f7fafc;
        border-radius: 16px;
        padding: 2rem;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .permission-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .permission-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .permission-icon {
        font-size: 2rem;
        width: 60px;
        height: 60px;
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .permission-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2d3748;
    }

    .permission-list {
        list-style: none;
    }

    .permission-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
        color: #4a5568;
        line-height: 1.6;
    }

    .permission-item::before {
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .can-list .permission-item::before {
        content: '✅';
        color: #48bb78;
    }

    .must-list .permission-item::before {
        content: '✍️';
        color: #667eea;
    }

    .cannot-list .permission-item::before {
        content: '❌';
        color: #fc8181;
    }

    .full-license {
        background: #f7fafc;
        border-radius: 16px;
        padding: 2.5rem;
        margin-top: 3rem;
    }

    .full-license-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .full-license-title {
        color: #2d3748;
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .full-license-subtitle {
        color: #718096;
        font-size: 1rem;
    }

    .license-text {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace;
        font-size: 0.95rem;
        line-height: 1.8;
        color: #4a5568;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 500px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
    }

    footer {
        text-align: center;
        padding: 2rem;
        font-size: 0.9rem;
        color: #718096;
        width: 100%;
        margin-top: auto;
    }

    footer a {
        color: #667eea;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    footer a:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    @media (prefers-color-scheme: dark) {
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f1f5f9;
        }

        header {
            background: rgba(30, 41, 59, 0.95);
            color: #cbd5e1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .license-container {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .quick-summary {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-left-color: #818cf8;
        }

        .summary-title {
            color: #f1f5f9;
        }

        .summary-content {
            color: #cbd5e1;
        }

        .permission-card {
            background: rgba(30, 41, 59, 0.7);
        }

        .permission-title {
            color: #f1f5f9;
        }

        .permission-item {
            color: #cbd5e1;
        }

        .full-license {
            background: rgba(30, 41, 59, 0.7);
        }

        .full-license-title {
            color: #f1f5f9;
        }

        .license-text {
            background: rgba(15, 23, 42, 0.9);
            color: #cbd5e1;
            border-color: #475569;
        }

        .back-link {
            color: #818cf8;
        }

        .back-link:hover {
            color: #c084fc;
        }
    }

    @media (max-width: 768px) {
        .license-container {
            padding: 2rem;
        }

        .license-title {
            font-size: 2rem;
        }

        .permissions-grid {
            grid-template-columns: 1fr;
        }

        .quick-summary {
            padding: 1.5rem;
        }

        .license-text {
            padding: 1.5rem;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .license-container {
            padding: 1.5rem;
        }

        .license-title {
            font-size: 1.75rem;
        }

        .permission-card {
            padding: 1.5rem;
        }
    }
</style>
</head>
<body>
<main>
    <div class="license-container">
        <div class="license-header">
            <h1 class="license-title">MIT License</h1>
            <p class="license-subtitle">
                Behind every piece of software lies the dedication, expertise, and passion of talented individuals.
                We champion a license that embodies the spirit of freedom and openness.
            </p>
        </div>

        <div class="quick-summary">
            <h2 class="summary-title">Quick Summary</h2>
            <p class="summary-content">
                At our core, we value the vibrant community of individuals who make each line of code possible.
                With pride, we champion a license that is renowned as one of the most liberal in the industry,
                empowering users to unleash their creativity, explore new possibilities, and shape their digital
                experiences.
            </p>
        </div>

        <div class="permissions-grid">
            <!-- Can -->
            <div class="permission-card">
                <div class="permission-header">
                    <div class="permission-icon"
                         style="background: linear-gradient(135deg, #48bb7815 0%, #38a16915 100%); color: #48bb78;">
                        ✅
                    </div>
                    <h3 class="permission-title">Can</h3>
                </div>
                <ul class="permission-list can-list">
                    <li class="permission-item">You may use the work commercially.</li>
                    <li class="permission-item">You may make changes to the work.</li>
                    <li class="permission-item">You may distribute the compiled code and/or source.</li>
                    <li class="permission-item">You may incorporate the work into something that has a more restrictive
                        license.
                    </li>
                    <li class="permission-item">You may use the work for private use.</li>
                </ul>
            </div>

            <!-- Must -->
            <div class="permission-card">
                <div class="permission-header">
                    <div class="permission-icon"
                         style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); color: #667eea;">
                        ✍️
                    </div>
                    <h3 class="permission-title">Must</h3>
                </div>
                <ul class="permission-list must-list">
                    <li class="permission-item">You must include the copyright notice in all copies or substantial uses
                        of the work.
                    </li>
                    <li class="permission-item">You must include the license notice in all copies or substantial uses of
                        the work.
                    </li>
                </ul>
            </div>

            <!-- Cannot -->
            <div class="permission-card">
                <div class="permission-header">
                    <div class="permission-icon"
                         style="background: linear-gradient(135deg, #fc818115 0%, #f5656515 100%); color: #fc8181;">
                        ❌
                    </div>
                    <h3 class="permission-title">Cannot</h3>
                </div>
                <ul class="permission-list cannot-list">
                    <li class="permission-item">The work is provided "as is". You may not hold the author liable.</li>
                </ul>
            </div>
        </div>

        <div class="full-license">
            <div class="full-license-header">
                <h2 class="full-license-title">MIT License</h2>
                <p class="full-license-subtitle">Full License Text</p>
            </div>
            <div class="license-text">
                Copyright © Grudinin Andrew (din9xtr)
                Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
                associated documentation files (the "Software"), to deal in the Software without restriction, including
                without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
                copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the
                following conditions:

                The above copyright notice and this permission notice shall be included in all copies or substantial
                portions of the Software.

                THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
                LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
                NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
                WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
                SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
            </div>
        </div>
    </div>
</main>
