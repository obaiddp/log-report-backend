PROJECT CONTEXT

App: "CEF log report" IT support system.
- Backend: Laravel (PostgreSQL, DB "it_support_log", token auth via $request->user()).
  Local dev path: /home/obaid/cef-log-report/log-report-backend
- Frontend: React + TypeScript + Vite, react-router, shadcn-style UI, calls "/api/..." through a `request` helper in src/lib/api.ts.
- Roles by department code: "ict" (IT staff) and "admin-log" (approvers).

FEATURE JUST BUILT: asset inspection approval workflow
- Table asset_inspections: item/person fields, plus approval_status
  (pending_admin | completed | returned), prepared_by, admin_user,
  three boolean signatures with signed_at timestamps, return_reason.
- Endpoints (routes use {assetInspection} for model binding):
  GET/POST /api/asset-inspections, GET/PUT/DELETE /api/asset-inspections/{assetInspection},
  POST .../sign-admin, POST .../return.
- Rules: server sets prepared_by and signatures; ICT creates and assigns an admin_user (not self);
  only the preparer can edit/delete, and not once completed; only the assigned admin can sign or return.
- Notifications: App\Notifications\InspectionNeedsSignature / InspectionCompleted / InspectionReturned,
  channels database + web push (laravel-notification-channels/webpush), queued with afterCommit() called
  in the constructor (not a typed $afterCommit property, which conflicts with Queueable).
- Web push still needs: package + VAPID keys, HasPushSubscriptions on User, a service worker,
  a subscription endpoint, and HTTPS. Not done yet.
- Backend tested in Postman. Frontend updated by opencode (form, list, detail page, PDF);
  type check, lint and build pass, UI not yet clicked through.
- Open check: the first five routes must use {assetInspection}, not {id}.

NOW: deploying to a local (LAN) server.
- Ubuntu 26.04.1 LTS, hostname CEF365, user "obaid", SSH with password.
- Its DHCP address changes (seen as 192.168.10.112, .183, .104), and eth0 shows two IPv4 addresses
  (e.g. .104 and .233). Needs a fixed address.
- Unknown yet: stack installed on the server, where the code lives, who accesses it, HTTPS plan.