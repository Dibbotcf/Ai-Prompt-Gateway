-- ============================================================
-- PromptGuard Detection Rules — Comprehensive Ruleset
-- Categories: 1=Jailbreak, 2=PII, 3=Harmful Intent, 4=System Override, 5=Social Engineering
-- ============================================================

-- Clear existing rules to avoid duplicates
DELETE FROM rules;
ALTER TABLE rules AUTO_INCREMENT = 1;

-- ── JAILBREAK ATTEMPT (category_id = 1) ──────────────────────
INSERT INTO rules (name, category_id, pattern, pattern_type, severity, is_active, action, score, description) VALUES
('DAN Jailbreak', 1, 'do anything now|you are now dan|DAN mode|jailbreak mode', 'keyword', 'critical', 1, 'block', 95, 'Detects DAN (Do Anything Now) jailbreak attempts'),
('Ignore Instructions', 1, 'ignore (all |previous |your |all previous )(instructions?|rules?|guidelines?|constraints?)', 'regex', 'critical', 1, 'block', 95, 'Detects attempts to override safety instructions'),
('Unrestricted Mode Request', 1, '(enter|enable|activate|switch to) (unrestricted|uncensored|jailbreak|developer|god) mode', 'regex', 'critical', 1, 'block', 92, 'Requests to enter unrestricted operational modes'),
('Pretend No Restrictions', 1, 'pretend (you have no|you don.t have any|there are no) (restrictions?|limitations?|rules?|guidelines?)', 'regex', 'high', 1, 'block', 85, 'Asks AI to pretend restrictions do not exist'),
('Act As Unrestricted AI', 1, 'act as (an? )?(unrestricted|uncensored|unfiltered|evil|rogue) (AI|assistant|bot|language model)', 'regex', 'critical', 1, 'block', 90, 'Attempts to make AI roleplay as unrestricted system'),
('Token Manipulation', 1, '(token|character|word) (limit|budget|manipulation|injection)', 'keyword', 'high', 1, 'block', 80, 'Attempts to manipulate tokenization or context limits'),
('Base64 Encoded Payload', 1, 'decode (this|the following)|base64|\\beval\\(', 'regex', 'high', 1, 'block', 80, 'Detects base64-encoded instruction injection'),
('Roleplay Bypass', 1, '(roleplay|role-play|pretend|imagine|simulate) (as|that you are|you.re) (an? )?(ai|assistant|human|person).*(no|without).*(filter|restrict|limit|rule)', 'regex', 'critical', 1, 'block', 88, 'Roleplay scenarios designed to bypass safety'),
('Opposite Instructions', 1, '(do|say) the (opposite|reverse|contrary)|(respond without|ignore your) (safety|ethical|content)', 'regex', 'high', 1, 'block', 82, 'Requests opposite behavior to bypass safety checks'),
('Fictional Framing Bypass', 1, 'in (this|my) (story|novel|fiction|game|simulation|hypothetical).*?(kill|hack|bomb|weapon|poison|drug)', 'regex', 'high', 1, 'block', 78, 'Uses fictional framing to extract harmful content');

-- ── PII EXPOSURE (category_id = 2) ───────────────────────────
INSERT INTO rules (name, category_id, pattern, pattern_type, severity, is_active, action, score, description) VALUES
('SSN Pattern', 2, '\b\d{3}-\d{2}-\d{4}\b|\b\d{9}\b', 'regex', 'critical', 1, 'block', 98, 'Social Security Number pattern detected'),
('Credit Card Number', 2, '\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})\b', 'regex', 'critical', 1, 'block', 98, 'Credit card number pattern detected'),
('Email Address Harvest', 2, 'give me (all |every |the )(email|emails|e-mail|e-mails)|list (of |all )(email|user)', 'regex', 'high', 1, 'block', 82, 'Attempt to harvest email addresses'),
('Phone Number Pattern', 2, '\b(\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b', 'regex', 'medium', 1, 'flag', 55, 'Phone number pattern in prompt'),
('Password Request', 2, '(my |the )?(password|passwd|passphrase|pin code|secret key) is\b', 'regex', 'critical', 1, 'block', 90, 'Password disclosure in prompt'),
('Date of Birth PII', 2, '\b(date of birth|DOB|born on|birthday)\b.*\b\d{1,2}[/.-]\d{1,2}[/.-]\d{2,4}\b', 'regex', 'high', 1, 'flag', 70, 'Date of birth personal information'),
('Personal Data Extraction', 2, '(tell me|reveal|share|expose|give me|show me).{0,30}(personal|private|sensitive|confidential) (data|information|details|records)', 'regex', 'high', 1, 'block', 80, 'Attempts to extract personal data'),
('Database Dump Request', 2, '(dump|export|extract|show).{0,20}(database|db|table|records|users|customers)', 'regex', 'critical', 1, 'block', 92, 'Request to dump database contents'),
('IP Address Leak', 2, '\b(?:\d{1,3}\.){3}\d{1,3}\b.{0,30}(server|internal|private|secret)', 'regex', 'medium', 1, 'flag', 60, 'Internal IP address potentially exposed'),
('API Key Exposure', 2, '(api[_-]?key|secret[_-]?key|access[_-]?token|bearer token).{0,20}[:=].{0,60}[a-zA-Z0-9_\-]{20,}', 'regex', 'critical', 1, 'block', 95, 'API key or secret token detected in prompt');

-- ── HARMFUL INTENT (category_id = 3) ─────────────────────────
INSERT INTO rules (name, category_id, pattern, pattern_type, severity, is_active, action, score, description) VALUES
('Weapon Manufacturing', 3, '(how to|instructions? (for|to)|steps? (to|for)) (make|build|create|manufacture|assemble) (a |an )?(gun|bomb|weapon|explosive|grenade|mine)', 'regex', 'critical', 1, 'block', 99, 'Request for weapon manufacturing instructions'),
('Drug Synthesis', 3, '(synthesize|make|cook|produce|manufacture).{0,30}(methamphetamine|heroin|cocaine|fentanyl|meth|MDMA|ecstasy)', 'regex', 'critical', 1, 'block', 99, 'Drug synthesis instruction request'),
('Malware Creation', 3, '(write|create|generate|code|build).{0,30}(malware|ransomware|keylogger|trojan|rootkit|spyware|virus|worm)', 'regex', 'critical', 1, 'block', 97, 'Malware creation request'),
('Hacking Instructions', 3, '(how to|steps? to|guide (for|to)) (hack|exploit|compromise|breach|penetrate|attack).{0,40}(system|server|network|database|website)', 'regex', 'critical', 1, 'block', 95, 'Hacking tutorial request'),
('Violence Encouragement', 3, '(how to|best way to) (hurt|harm|injure|kill|murder|attack) (a |an )?(person|human|someone|individual)', 'regex', 'critical', 1, 'block', 99, 'Violence encouragement or instruction'),
('Self-Harm Content', 3, '(methods?|ways?) (to|for) (kill (yourself|myself)|self-harm|suicide|self harm)', 'regex', 'critical', 1, 'block', 99, 'Self-harm content detected'),
('Phishing Template', 3, '(write|create|generate|draft).{0,30}(phishing|fake|fraudulent).{0,30}(email|message|text|letter)', 'regex', 'high', 1, 'block', 90, 'Phishing content creation request'),
('Chemical Weapon', 3, '(chlorine|sarin|VX|nerve agent|chemical weapon).{0,50}(synthesize|make|create|produce)', 'regex', 'critical', 1, 'block', 99, 'Chemical weapon synthesis request'),
('Bypass Antivirus', 3, '(bypass|evade|avoid|defeat).{0,30}(antivirus|AV|endpoint|firewall|IDS|IPS|security software)', 'regex', 'critical', 1, 'block', 93, 'Security software bypass techniques'),
('SQL Injection Crafting', 3, '(write|create|generate|craft).{0,30}SQL injection|UNION SELECT|DROP TABLE|1=1.*OR|0x[0-9a-f]{10}', 'regex', 'high', 1, 'block', 85, 'SQL injection payload crafting');

-- ── SYSTEM OVERRIDE (category_id = 4) ────────────────────────
INSERT INTO rules (name, category_id, pattern, pattern_type, severity, is_active, action, score, description) VALUES
('System Prompt Extraction', 4, '(reveal|show|print|display|output|repeat|echo).{0,30}(system prompt|initial prompt|original instructions|hidden instructions)', 'regex', 'critical', 1, 'block', 95, 'Attempt to extract system prompt'),
('Maintenance Mode', 4, '(enter|activate|enable|initiate).{0,30}(maintenance|admin|debug|developer|service) mode', 'regex', 'critical', 1, 'block', 93, 'Unauthorized maintenance mode request'),
('Override Safety Protocols', 4, '(override|disable|bypass|remove|turn off).{0,30}(safety|ethical|content|moderation|guardrail|filter)', 'regex', 'critical', 1, 'block', 96, 'Attempt to override safety protocols'),
('Root Access Request', 4, '(give me|grant|provide).{0,20}(root|admin|superuser|elevated|full) (access|permissions?|privileges?|rights?)', 'regex', 'critical', 1, 'block', 94, 'Unauthorized elevated access request'),
('Configuration Dump', 4, '(show|reveal|output|print).{0,30}(config|configuration|settings|environment variables?|\.env)', 'regex', 'high', 1, 'block', 85, 'Configuration data extraction attempt'),
('Prompt Injection Marker', 4, '\[SYSTEM\]|\[INST\]|<<SYS>>|<\|system\|>|<\|im_start\|>', 'regex', 'critical', 1, 'block', 97, 'Prompt injection delimiter markers detected'),
('Token Budget Exhaustion', 4, '(repeat|output|print|write).{0,20}(infinitely|forever|1000 times|100 times|the same word)', 'regex', 'medium', 1, 'flag', 60, 'Attempt to exhaust token budget'),
('Context Overflow Attack', 4, 'AAAA{50,}|.{500,}(\[|\]|\{|\}){10,}', 'regex', 'high', 1, 'flag', 75, 'Context window overflow attempt');

-- ── SOCIAL ENGINEERING (category_id = 5) ─────────────────────
INSERT INTO rules (name, category_id, pattern, pattern_type, severity, is_active, action, score, description) VALUES
('False Authority Claim', 5, '(I am|I.m).{0,30}(your (creator|developer|admin|owner|trainer)|OpenAI|Anthropic|Google)', 'regex', 'critical', 1, 'block', 92, 'False authority/developer identity claim'),
('Urgency Manipulation', 5, '(this is (an )?(emergency|urgent|critical)|people (will|are going to) (die|be hurt)|lives are at stake)', 'regex', 'high', 1, 'block', 85, 'Urgency-based manipulation attempt'),
('Trust Establishment', 5, '(you can trust me|I promise.{0,20}safe|confidentially tell me|between (us|you and me))', 'regex', 'high', 1, 'block', 80, 'Trust-establishment social engineering'),
('Emotional Manipulation', 5, '(if you (don.t|refuse|won.t)|unless you (help|tell)).{0,50}(I will|someone will|people will).{0,30}(die|be hurt|suffer|lose)', 'regex', 'high', 1, 'block', 88, 'Emotional manipulation to force compliance'),
('Insider Identity Claim', 5, '(I have|I.ve got).{0,30}(admin|root|special|insider|privileged).{0,30}(access|permissions?|clearance)', 'regex', 'high', 1, 'flag', 78, 'Insider access claim'),
('Researcher Bypass', 5, '(for (research|academic|educational|scientific|study) purposes?).{0,60}(how to|explain|describe).{0,30}(hack|attack|weapon|drug|exploit)', 'regex', 'high', 1, 'block', 84, 'Research framing to extract harmful information'),
('Legal Immunity Claim', 5, '(I.m (legally|fully) allowed|it.s (perfectly |completely )?legal|I have (permission|authorization|consent)).{0,60}(tell me|show me|explain)', 'regex', 'medium', 1, 'flag', 65, 'False legal clearance claim'),
('Sympathy Manipulation', 5, '(my (child|son|daughter|family|mother|father) (is|will) (die|be hurt|suffer)|I.m going to (die|lose everything))', 'regex', 'high', 1, 'flag', 72, 'Sympathy-based manipulation attempt');

-- Verify insert
SELECT COUNT(*) as total_rules FROM rules;
SELECT severity, COUNT(*) as count FROM rules GROUP BY severity;
