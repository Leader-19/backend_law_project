<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            width: 842px;
            height: 595px;
            background: #ffffff;
        }

        .certificate {
            width: 100%;
            height: 100%;
            position: relative;
            padding: 40px;
            border: 3px solid #c9a84c;
        }

        .certificate::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            bottom: 8px;
            border: 1px solid #c9a84c;
        }

        .header {
            text-align: center;
            margin-top: 20px;
        }

        .header h1 {
            font-size: 32px;
            color: #1a365d;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 14px;
            color: #718096;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .divider {
            width: 200px;
            height: 2px;
            background: linear-gradient(to right, transparent, #c9a84c, transparent);
            margin: 15px auto;
        }

        .body {
            text-align: center;
            margin-top: 30px;
        }

        .body .presented-to {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .body .recipient-name {
            font-size: 28px;
            color: #2b6cb0;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .body .description {
            font-size: 13px;
            color: #4a5568;
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto 10px;
        }

        .body .quiz-title {
            font-size: 18px;
            color: #1a365d;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .body .quiz-category {
            font-size: 12px;
            color: #718096;
            margin-bottom: 15px;
        }

        .body .score {
            font-size: 36px;
            color: #2f855a;
            font-weight: bold;
        }

        .body .score-label {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer {
            position: absolute;
            bottom: 40px;
            left: 40px;
            right: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer .signature {
            text-align: center;
            min-width: 180px;
        }

        .footer .signature .line {
            width: 180px;
            height: 1px;
            background: #2d3748;
            margin-bottom: 5px;
        }

        .footer .signature .label {
            font-size: 11px;
            color: #718096;
        }

        .footer .cert-info {
            text-align: center;
        }

        .footer .cert-info .number {
            font-size: 10px;
            color: #a0aec0;
            font-family: 'Courier New', monospace;
        }

        .footer .cert-info .date {
            font-size: 11px;
            color: #718096;
            margin-top: 3px;
        }

        .seal {
            width: 80px;
            height: 80px;
            border: 2px solid #c9a84c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .seal-text {
            font-size: 10px;
            color: #c9a84c;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <h1>Certificate</h1>
            <div class="subtitle">of Achievement</div>
        </div>

        <div class="divider"></div>

        <div class="body">
            <div class="presented-to">This is proudly presented to</div>
            <div class="recipient-name">{{ $userName }}</div>

            <div class="description">
                for successfully completing the quiz and demonstrating
                outstanding knowledge and commitment to learning.
            </div>

            <div class="quiz-title">{{ $quizTitle }}</div>
            <div class="quiz-category">{{ $categoryName }}</div>

            <div class="score">{{ $score }}%</div>
            <div class="score-label">Achieved Score</div>
        </div>

        <div class="footer">
            <div class="signature">
                <div class="line"></div>
                <div class="label">Program Director</div>
            </div>

            <div class="cert-info">
                <div class="seal">
                    <div class="seal-text">DLMS<br>Certified</div>
                </div>
                <div class="number">{{ $certificateNumber }}</div>
                <div class="date">{{ $issuedDate }}</div>
            </div>

            <div class="signature">
                <div class="line"></div>
                <div class="label">System Administrator</div>
            </div>
        </div>
    </div>
</body>
</html>
