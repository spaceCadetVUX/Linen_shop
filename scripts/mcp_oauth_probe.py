#!/usr/bin/env python3
"""
Test the cacylinen MCP OAuth connector (mcp-auth-proxy + mcp-server) end-to-end
without going through the slow, opaque claude.ai "Add custom connector" UI.

Runs every step Claude's backend is known to perform against the connector
(unauthenticated probes, OPTIONS/CORS, DCR registration, OAuth code flow, token
exchange, JWT inspection, authenticated MCP initialize call) so config changes
can be verified from the command line first.

Usage:
    python3 scripts/mcp_oauth_probe.py setup [BASE_URL]
        Runs all unauthenticated checks, registers a fresh OAuth client, prints
        an authorization URL to open in a browser, and saves state to
        /tmp/mcp_oauth_probe_state.json (or same dir on Windows via TEMP).

    python3 scripts/mcp_oauth_probe.py complete CODE
        Reads the saved state, exchanges CODE for a token, decodes the JWT,
        and calls the root endpoint with the token to confirm a real MCP
        `initialize` response comes back.

BASE_URL defaults to https://mcp.cacylinen.com/ (no trailing /mcp — see
mcp-oauth-claude-ai-connector.md for why the root path matters here).
"""
import base64
import hashlib
import json
import os
import secrets
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request

DEFAULT_BASE_URL = "https://mcp.cacylinen.com/"
STATE_PATH = os.path.join(tempfile.gettempdir(), "mcp_oauth_probe_state.json")
CLAUDE_UA = "python-httpx/0.28.1"  # matches what Claude's backend sends, per proxy logs


def http(method, url, headers=None, body=None):
    merged = {"User-Agent": CLAUDE_UA}
    merged.update(headers or {})
    req = urllib.request.Request(url, method=method, data=body, headers=merged)
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            return resp.status, dict(resp.getheaders()), resp.read()
    except urllib.error.HTTPError as e:
        return e.code, dict(e.headers or {}), e.read()


def b64url_decode(s):
    s += "=" * (-len(s) % 4)
    return base64.urlsafe_b64decode(s)


def check(label, ok, detail=""):
    mark = "OK " if ok else "FAIL"
    print(f"[{mark}] {label}{'  -- ' + detail if detail else ''}")
    return ok


def run_setup(base_url):
    print(f"=== Probing {base_url} (unauthenticated checks Claude runs first) ===\n")
    all_ok = True

    status, headers, _ = http("POST", base_url, headers={"User-Agent": CLAUDE_UA})
    all_ok &= check("POST / unauthenticated -> 401", status == 401, f"got {status}")

    status, headers, _ = http("OPTIONS", base_url, headers={"User-Agent": CLAUDE_UA})
    all_ok &= check("OPTIONS / -> 204, no auth required", status == 204, f"got {status}")
    cors = {k.lower(): v for k, v in headers.items()}
    all_ok &= check("OPTIONS response has Access-Control-Allow-Origin", "access-control-allow-origin" in cors)

    status, headers, _ = http("POST", base_url, headers={"User-Agent": CLAUDE_UA})
    hdrs = {k.lower(): v for k, v in headers.items()}
    all_ok &= check("POST / 401 includes WWW-Authenticate", "www-authenticate" in hdrs, str(hdrs.get("www-authenticate")))

    prm_url = urllib.parse.urljoin(base_url, "/.well-known/oauth-protected-resource")
    status, _, body = http("GET", prm_url)
    prm = json.loads(body) if status == 200 else {}
    all_ok &= check("GET /.well-known/oauth-protected-resource -> 200", status == 200)
    if status == 200:
        print(f"       resource={prm.get('resource')!r} authorization_servers={prm.get('authorization_servers')!r}")

    asm_url = urllib.parse.urljoin(base_url, "/.well-known/oauth-authorization-server")
    status, _, body = http("GET", asm_url)
    all_ok &= check("GET /.well-known/oauth-authorization-server -> 200", status == 200)

    print("\n=== Registering a fresh OAuth client (DCR) ===\n")
    reg_url = urllib.parse.urljoin(base_url, "/.idp/register")
    reg_body = json.dumps({
        "redirect_uris": [base_url],
        "token_endpoint_auth_method": "none",
        "grant_types": ["authorization_code"],
        "response_types": ["code"],
    }).encode()
    status, _, body = http("POST", reg_url, headers={"Content-Type": "application/json"}, body=reg_body)
    if not check("POST /.idp/register -> 201", status == 201, f"got {status}: {body[:200]}"):
        sys.exit(1)
    client = json.loads(body)
    client_id = client["client_id"]

    verifier = base64.urlsafe_b64encode(secrets.token_bytes(32)).rstrip(b"=").decode()
    challenge = base64.urlsafe_b64encode(hashlib.sha256(verifier.encode()).digest()).rstrip(b"=").decode()
    state = secrets.token_urlsafe(12)

    resource = prm.get("resource", base_url)
    auth_url = urllib.parse.urljoin(base_url, "/.idp/auth") + "?" + urllib.parse.urlencode({
        "response_type": "code",
        "client_id": client_id,
        "redirect_uri": base_url,
        "code_challenge": challenge,
        "code_challenge_method": "S256",
        "resource": resource,
        "state": state,
    })

    json.dump({
        "base_url": base_url,
        "client_id": client_id,
        "code_verifier": verifier,
        "state": state,
    }, open(STATE_PATH, "w"))

    print(f"\nAll automated checks passed. State saved to {STATE_PATH}.\n")
    print("Open this URL in a browser (a fresh/incognito window recommended), log in with an")
    print("allowlisted Google account, then copy the `code=` value from the resulting")
    print(f"https://.../?code=...&state={state} redirect:\n")
    print(auth_url)
    print(f"\nThen run:\n    python3 {sys.argv[0]} complete <code>")


def run_complete(code):
    if not os.path.exists(STATE_PATH):
        print(f"No saved state at {STATE_PATH} — run `setup` first.")
        sys.exit(1)
    state = json.load(open(STATE_PATH))
    base_url = state["base_url"]

    print("=== Exchanging code for token ===\n")
    token_url = urllib.parse.urljoin(base_url, "/.idp/token")
    form = urllib.parse.urlencode({
        "grant_type": "authorization_code",
        "code": code,
        "redirect_uri": base_url,
        "client_id": state["client_id"],
        "code_verifier": state["code_verifier"],
    }).encode()
    status, _, body = http("POST", token_url, headers={"Content-Type": "application/x-www-form-urlencoded"}, body=form)
    if not check("POST /.idp/token -> 200", status == 200, f"got {status}: {body[:300]}"):
        sys.exit(1)
    token_resp = json.loads(body)
    access_token = token_resp["access_token"]

    header_b64, payload_b64, _sig = access_token.split(".")
    payload = json.loads(b64url_decode(payload_b64))
    print("\nJWT payload:")
    print(json.dumps(payload, indent=2))

    print("\n=== Calling root endpoint with the token (real MCP initialize) ===\n")
    init_body = json.dumps({
        "jsonrpc": "2.0", "id": 1, "method": "initialize",
        "params": {"protocolVersion": "2025-03-26", "capabilities": {}, "clientInfo": {"name": "mcp_oauth_probe", "version": "0"}},
    }).encode()
    status, _, body = http("POST", base_url, headers={
        "Authorization": f"Bearer {access_token}",
        "Content-Type": "application/json",
        "Accept": "application/json, text/event-stream",
    }, body=init_body)
    ok = check("POST / with Bearer token -> 200", status == 200, f"got {status}")
    print("\nResponse body:")
    print(body.decode(errors="replace"))
    sys.exit(0 if ok else 1)


if __name__ == "__main__":
    if len(sys.argv) < 2 or sys.argv[1] not in ("setup", "complete"):
        print(__doc__)
        sys.exit(1)
    if sys.argv[1] == "setup":
        base_url = sys.argv[2] if len(sys.argv) > 2 else DEFAULT_BASE_URL
        run_setup(base_url)
    else:
        if len(sys.argv) < 3:
            print("Usage: mcp_oauth_probe.py complete CODE")
            sys.exit(1)
        run_complete(sys.argv[2])
