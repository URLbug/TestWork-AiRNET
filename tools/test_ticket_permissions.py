"""Integration tests for changing a ticket source through the HTTP API.

Run with:
    python -m unittest discover -s backend/tests -v

The app must be running (defaults to its frontend proxy at http://localhost:5173).
Set AIRNET_API_URL to use another base URL.
"""

import http.cookiejar
import json
import os
import urllib.error
import urllib.request
import unittest


API_URL = os.environ.get("AIRNET_API_URL", "http://localhost:5173").rstrip("/")
ACTIVE_TICKET_ID = 101
ARCHIVED_TICKET_ID = 104


class ApiClient:
    """Small standard-library client that keeps the API session cookie."""

    def __init__(self, role):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self.cookies)
        )
        status, response = self.request(
            "POST", "/api/demo/login", {"role": role}
        )
        if status != 200:
            raise RuntimeError(f"Login as {role!r} failed: HTTP {status}: {response}")
        self.csrf_token = response["csrf"]

    def request(self, method, path, payload=None):
        headers = {"Accept": "application/json"}
        data = None
        if payload is not None:
            headers["Content-Type"] = "application/json"
            data = json.dumps(payload).encode("utf-8")
        if method == "POST" and path.endswith("/source"):
            headers["X-CSRF-Token"] = self.csrf_token

        request = urllib.request.Request(
            f"{API_URL}{path}", data=data, headers=headers, method=method
        )
        try:
            response = self.opener.open(request, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        body = response.read().decode("utf-8")
        return response.code, json.loads(body) if body else {}

    def ticket(self, ticket_id):
        return self.request("GET", f"/api/tickets/{ticket_id}")

    def sources(self):
        status, response = self.request("GET", "/api/sources")
        if status != 200:
            raise RuntimeError(f"Could not read sources: HTTP {status}: {response}")
        return response["sources"]

    def change_source(self, ticket_id, source_id):
        return self.request(
            "POST", f"/api/tickets/{ticket_id}/source", {"source_id": source_id}
        )


class TicketSourcePermissionTests(unittest.TestCase):
    def test_viewer_cannot_change_source_of_active_ticket(self):
        client = ApiClient("viewer")
        status, before_response = client.ticket(ACTIVE_TICKET_ID)
        self.assertEqual(status, 200, before_response)
        original_source_id = before_response["ticket"]["source"]["id"]
        alternate_source_id = next(
            source["id"]
            for source in client.sources()
            if source["id"] != original_source_id
        )

        status, response = client.change_source(
            ACTIVE_TICKET_ID, alternate_source_id
        )

        self.assertEqual(status, 403, response)
        status, after_response = client.ticket(ACTIVE_TICKET_ID)
        self.assertEqual(status, 200, after_response)
        self.assertEqual(
            after_response["ticket"]["source"]["id"], original_source_id
        )

    def test_editor_can_change_source_of_active_ticket(self):
        client = ApiClient("editor")
        status, before_response = client.ticket(ACTIVE_TICKET_ID)
        self.assertEqual(status, 200, before_response)
        original_source_id = before_response["ticket"]["source"]["id"]
        alternate_source_id = next(
            source["id"]
            for source in client.sources()
            if source["id"] != original_source_id
        )

        try:
            status, response = client.change_source(
                ACTIVE_TICKET_ID, alternate_source_id
            )
            self.assertEqual(status, 200, response)
            self.assertEqual(
                response["ticket"]["source"]["id"], alternate_source_id
            )
        finally:
            # Restore the seeded source so repeated runs start from the same data.
            restore_status, restore_response = client.change_source(
                ACTIVE_TICKET_ID, original_source_id
            )
            self.assertEqual(restore_status, 200, restore_response)

    def test_viewer_cannot_change_source_of_archived_ticket(self):
        client = ApiClient("viewer")
        status, before_response = client.ticket(ARCHIVED_TICKET_ID)
        self.assertEqual(status, 200, before_response)
        original_source_id = before_response["ticket"]["source"]["id"]
        alternate_source_id = next(
            source["id"]
            for source in client.sources()
            if source["id"] != original_source_id
        )

        status, response = client.change_source(
            ARCHIVED_TICKET_ID, alternate_source_id
        )

        self.assertEqual(status, 403, response)
        status, after_response = client.ticket(ARCHIVED_TICKET_ID)
        self.assertEqual(status, 200, after_response)
        self.assertEqual(
            after_response["ticket"]["source"]["id"], original_source_id
        )

    def test_editor_cannot_change_source_of_archived_ticket(self):
        client = ApiClient("editor")
        status, before_response = client.ticket(ARCHIVED_TICKET_ID)
        self.assertEqual(status, 200, before_response)
        original_source_id = before_response["ticket"]["source"]["id"]
        alternate_source_id = next(
            source["id"]
            for source in client.sources()
            if source["id"] != original_source_id
        )

        status, response = client.change_source(
            ARCHIVED_TICKET_ID, alternate_source_id
        )

        self.assertEqual(status, 403, response)
        status, after_response = client.ticket(ARCHIVED_TICKET_ID)
        self.assertEqual(status, 200, after_response)
        self.assertEqual(
            after_response["ticket"]["source"]["id"], original_source_id
        )


if __name__ == "__main__":
    unittest.main()
