#!/usr/bin/env python3
"""
Recovery: substitute PLACEHOLDER<n> code blocks with pre-audit (07e554d) actual code.

For 9 of 10 affected files the order of `_code` blocks is preserved between
pre-audit and current state, so we map by index. For event_sourcing.html.twig
the audit added two phantom blocks (Outbox relay + outbox_position SQL) without
real code. Those map to pre-audit by filename for the 19 that match; the two
phantom callouts are removed in a separate step (see recover_outbox_phantoms).

Usage: python3 scripts/recover-placeholders.py
"""
from __future__ import annotations

import re
import subprocess
from pathlib import Path

PRE_AUDIT_REF = "07e554d"
TEMPLATES = Path("templates/ddd")

# 9 files where index-based mapping works; event_sourcing handled separately
INDEX_FILES = [
    "cqrs",
    "sagas",
    "implementation_in_symfony",
    "performance_aspects",
    "testing_ddd",
    "ddd_pain_points",
    "anti_patterns",
    "when_not_to_use_ddd",
    "case_study",
]

CODE_BLOCK_RE = re.compile(
    r"\{%\s*set\s+_code\s*%\}(.*?)\{%\s*endset\s*%\}",
    re.DOTALL,
)
# Note: the audit substitution wrapped placeholders with NUL bytes (\x00) instead of spaces.
PLACEHOLDER_RE = re.compile(
    r"\{%\s*set\s+_code\s*%\}[\s\x00]*PLACEHOLDER(\d+)[\s\x00]*\{%\s*endset\s*%\}"
)


def git_show(ref: str, path: str) -> str:
    return subprocess.check_output(["git", "show", f"{ref}:{path}"], text=True)


def extract_code_bodies(content: str) -> list[str]:
    """Return list of inner code body strings (between set _code and endset)."""
    return CODE_BLOCK_RE.findall(content)


def restore_by_index(file_stem: str) -> int:
    """Substitute placeholders by index from pre-audit. Returns count restored."""
    target = TEMPLATES / f"{file_stem}.html.twig"
    cur = target.read_text()
    pre = git_show(PRE_AUDIT_REF, f"templates/ddd/{file_stem}.html.twig")
    pre_bodies = extract_code_bodies(pre)

    def repl(match: re.Match) -> str:
        idx = int(match.group(1))
        if idx >= len(pre_bodies):
            raise RuntimeError(f"{file_stem}: PLACEHOLDER{idx} has no pre-audit body (only {len(pre_bodies)})")
        body = pre_bodies[idx]
        return "{% set _code %}" + body + "{% endset %}"

    new = PLACEHOLDER_RE.sub(repl, cur)
    if new == cur:
        return 0
    target.write_text(new)
    placeholders = len(PLACEHOLDER_RE.findall(cur))
    return placeholders


def restore_event_sourcing() -> int:
    """event_sourcing has 21 placeholders vs 19 pre-audit codes.
    Map by filename: PLACEHOLDER 0-9 → pre 0-9; 10,11 are phantom (no source);
    12-20 → pre 10-18.
    """
    file_stem = "event_sourcing"
    target = TEMPLATES / f"{file_stem}.html.twig"
    cur = target.read_text()
    pre = git_show(PRE_AUDIT_REF, f"templates/ddd/{file_stem}.html.twig")
    pre_bodies = extract_code_bodies(pre)
    assert len(pre_bodies) == 19, f"expected 19 pre-audit, got {len(pre_bodies)}"

    # Map current placeholder index → pre-audit index (None = phantom, leave for later removal)
    mapping: dict[int, int] = {}
    for i in range(0, 10):
        mapping[i] = i
    for i in range(12, 21):
        mapping[i] = i - 2

    def repl(match: re.Match) -> str:
        idx = int(match.group(1))
        if idx in mapping:
            body = pre_bodies[mapping[idx]]
            return "{% set _code %}" + body + "{% endset %}"
        # Phantom – leave a marker that the next step removes the surrounding callout.
        return match.group(0)

    new = PLACEHOLDER_RE.sub(repl, cur)
    target.write_text(new)
    return sum(1 for k in mapping)


def remove_outbox_phantom_callouts() -> int:
    """Remove the two phantom Outbox callouts (PLACEHOLDER10 and PLACEHOLDER11) from event_sourcing.
    Each callout has the structure:
        {% set _callout_body %}
            <h3 ...>...</h3>
            {% set _code %} PLACEHOLDER<n> {% endset %}
            {% include '_partials/code_block.html.twig' with { ... } %}
        {% endset %}
        {% include '_partials/callout.html.twig' with { type: 'pattern', body: _callout_body } %}
    """
    target = TEMPLATES / "event_sourcing.html.twig"
    cur = target.read_text()
    removed = 0
    for ph_idx in (10, 11):
        # Match the entire {% set _callout_body %} ... {% include callout %} block
        # that contains the specific PLACEHOLDER<ph_idx>. Note: PLACEHOLDER tokens
        # are wrapped with NUL bytes by the audit script.
        pattern = re.compile(
            r"\n\s*\{%\s*set\s+_callout_body\s*%\}"
            r"(?:(?!\{%\s*set\s+_callout_body\s*%\}).)*?"
            r"PLACEHOLDER" + str(ph_idx) + r"(?=[\s\x00])"
            r".*?"
            r"\{%\s*include\s+'_partials/callout\.html\.twig'\s+with\s+\{[^}]*\}\s*%\}",
            re.DOTALL,
        )
        new = pattern.sub("", cur, count=1)
        if new != cur:
            removed += 1
            cur = new
    target.write_text(cur)
    return removed


def main() -> None:
    total = 0
    for stem in INDEX_FILES:
        n = restore_by_index(stem)
        print(f"  {stem}: restored {n} code blocks")
        total += n
    n = restore_event_sourcing()
    print(f"  event_sourcing: restored {n} code blocks (2 phantoms remain)")
    total += n
    n = remove_outbox_phantom_callouts()
    print(f"  event_sourcing: removed {n} phantom callouts")
    print(f"\nTotal placeholders restored: {total}")
    # Sanity: zero PLACEHOLDER<n> tokens left
    leftover = subprocess.run(
        ["grep", "-arc", "PLACEHOLDER", "templates/ddd/"],
        capture_output=True, text=True
    )
    leftovers = [ln for ln in leftover.stdout.splitlines() if not ln.endswith(":0")]
    if leftovers:
        print("\n⚠ Remaining PLACEHOLDER occurrences:")
        for ln in leftovers:
            print(f"  {ln}")
    else:
        print("\n✓ No PLACEHOLDER tokens remain in templates/ddd/")


if __name__ == "__main__":
    main()
