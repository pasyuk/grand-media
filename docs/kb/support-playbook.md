# Support Playbook

This playbook covers Gmail and WordPress.org forum support for Gmedia Gallery.

## Policy

- Current support policy: best effort, no public SLA promise yet.
- Default mode for email replies: draft-only.
- Do not send, archive, delete, move, or label emails unless explicitly asked.
- Turn repeated questions into documentation and small GitHub issues.

## Gmail Scope

- Support inbox: `gmediafolder@gmail.com`.
- Primary scope: Gmail label `Gmedia Support`.
- Secondary context: `SENT` mail for previous support replies and tone/history.
- Do not scan unlabeled `INBOX` unless explicitly asked or when setting up/repairing the support label workflow.

## WordPress.org Forum Scope

- Forum: `https://wordpress.org/support/plugin/grand-media/`.
- Check opportunistically and after releases until a formal cadence is chosen.
- Prioritize:
  - Security or data loss.
  - Fatal errors.
  - Upload broken.
  - Galleries not displaying.
  - Paid-license confusion posted publicly.
  - Repeated how-to questions.

## Triage Categories

- `bug`: reproducible behavior defect.
- `security`: possible vulnerability or unsafe behavior.
- `licensing`: Freemius, legacy license, lost key, activation, premium access.
- `support`: answer-only or support-process task.
- `docs`: repeated question or missing article.
- `compatibility`: WordPress, PHP, browser, theme, or plugin compatibility.
- `wordpress-org`: forum, readme, SVN, or plugin directory issue.
- `freemius`: Freemius processing, checkout, account, updates, SDK, or premium archive flow.
- `module`: skin/module-specific issue.
- `frontend`: shortcode, block, template, public AJAX, or gallery display.
- `admin`: admin UI, upload, import, settings, modules, processors.

## Reply Workflow

1. Read the latest customer/forum message.
2. Identify plugin version, WordPress version, PHP version, module name, and affected page if provided.
3. Search current repo docs and issues.
4. Reproduce locally when practical.
5. If it is a bug, create or link a small GitHub issue.
6. Draft a concise reply with exact assumptions and next step.
7. If the question repeats, create or update a docs issue.

## Draft Or Issue Decision

Use a draft-only reply when:

- The question is a one-off how-to.
- The answer is already documented or can be answered from current plugin behavior.
- The customer needs a simple clarification and no code/docs change is needed.
- The request is account-specific and should stay out of public GitHub issues.

Create or link a GitHub issue when:

- A bug can be reproduced or has enough evidence to investigate.
- More than one user reports the same symptom.
- A support answer needs a docs article.
- A licensing/Freemius behavior needs verification.
- A compatibility report needs local testing.
- A release/readme/SVN/Freemius process gap is discovered.

Create a docs issue when:

- A question repeats.
- A reply requires more than a short answer.
- The user is confused by free vs premium boundaries.
- The setup path is unclear.
- A support answer would be useful on codeasily.com.

Do not create a public issue yet when:

- The report contains sensitive customer data that cannot be sanitized quickly.
- The topic is a possible security flaw and public details could increase risk before validation.
- The issue depends on a private license/payment/account record.
- The next step requires authorization, account access, or an exact external name that is not available.

## Privacy Rules

Do not put these into public GitHub issues or public forum replies:

- License keys.
- Customer personal data.
- Private domains unless the customer already posted them publicly and they are necessary.
- Email addresses beyond public usernames.
- Payment/order details.
- Exploit payloads or step-by-step security abuse instructions.
- Sensitive operational details.

## Draft Reply Shape

Use this compact structure:

```text
Hi [name],

Thanks for the details. Based on [versions/symptom], this looks like [short diagnosis or next check].

[One or two concrete steps, or what we are checking/fixing.]

If you can, please confirm [only missing facts needed].

Best,
Serhii
```

## Draft Templates

### Bug Or Repro Request

```text
Hi [name],

Thanks for the report. I need to reproduce this before changing the plugin behavior.

Please send:
- Gmedia Gallery version
- WordPress version
- PHP version
- The gallery/module name if this happens only with one gallery
- A screenshot or exact error text if available

I will check it against the current version and create a small bug ticket if I can reproduce it.

Best,
Serhii
```

### Confirmed Bug

```text
Hi [name],

Thanks for the details. I was able to reproduce this in the current version.

I created a small issue for the fix and will keep the change scoped to this behavior so it does not affect unrelated galleries/modules.

For now, the safest workaround is [workaround if confirmed].

Best,
Serhii
```

### Licensing Or Freemius

```text
Hi [name],

Thanks for reaching out. License/account issues need to be checked carefully because Gmedia supports both Freemius licenses and older legacy licenses.

Please do not post your license key publicly. If you already sent account details by email, I will check the license state there and reply with the next step.

Best,
Serhii
```

### How-To

```text
Hi [name],

You can do this from Gmedia Gallery by:

1. Go to [admin page].
2. Choose [album/gallery/module/action].
3. Save the changes.
4. Insert or update the gallery on the page/post.

If you tell me which module/gallery you are using, I can point to the exact setting.

Best,
Serhii
```

### Compatibility

```text
Hi [name],

This may be a compatibility issue, so I need the environment details before guessing.

Please confirm:
- WordPress version
- PHP version
- Gmedia Gallery version
- Theme name
- Whether this happens with other plugins temporarily disabled
- Browser console error, if there is one

I will compare that with the supported baseline and current known issues.

Best,
Serhii
```

### Docs Gap

```text
Hi [name],

Thanks, this question shows that the current documentation is not clear enough.

The short answer is: [answer].

I will also add this to the documentation so future users can find it without waiting for support.

Best,
Serhii
```

### Security Report

```text
Hi [name],

Thanks for reporting this. Please do not post exploit details publicly.

I will validate the report against the current plugin version first. If it is confirmed, I will handle it as a security fix and avoid sharing sensitive details in public issues or forum replies.

Best,
Serhii
```

## Public Issue Template

Use this shape after sanitizing details:

```markdown
## User Problem

[Short public-safe symptom.]

## Affected Surface

[admin/frontend/module/licensing/docs/compatibility]

## Evidence

- Plugin version:
- WordPress version:
- PHP version:
- Module/gallery if relevant:

## Expected Behavior

[What should happen.]

## Actual Behavior

[What happens, without private data.]

## Verification

- [ ] Reproduce locally
- [ ] Identify affected code path
- [ ] Add/update docs or tests if needed
- [ ] Verify fix/manual flow

## Privacy Check

- [ ] No license keys
- [ ] No customer personal data
- [ ] No private domains unless necessary and already public
- [ ] No payment/order details
- [ ] No exploit payloads
```

## Closing The Loop

After a support item is handled:

- If draft-only, keep the draft concise and wait for explicit send approval.
- If an issue was created, link it in the internal support note or draft reply only when appropriate.
- If a docs gap was found, create or update the docs issue.
- If a workaround was given, record whether it is temporary or recommended.
- If the answer depends on a future release, add it to release notes when the fix ships.
