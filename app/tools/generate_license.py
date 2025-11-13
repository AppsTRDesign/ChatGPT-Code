from __future__ import annotations

import argparse
from datetime import datetime, timezone

from dateutil.relativedelta import relativedelta

from app.core.license import PLAN_CHOICES, encode_license, machine_fingerprint


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Generate a license key for the Telegram management suite.")
    parser.add_argument(
        "--machine",
        help="Machine fingerprint to license. Defaults to the current device's fingerprint.",
    )
    parser.add_argument(
        "--plan",
        choices=sorted(PLAN_CHOICES.keys()),
        default="1m",
        help="Lisans süresi (1m, 3m, 6m)",
    )
    return parser


def main() -> None:
    parser = build_parser()
    args = parser.parse_args()
    machine_id = args.machine or machine_fingerprint()
    months_label, months = PLAN_CHOICES[args.plan]
    expires_at = datetime.now(timezone.utc) + relativedelta(months=months)
    key = encode_license(machine_id, args.plan)
    print("Machine ID:", machine_id)
    print("Plan:", months_label)
    print("Expires UTC:", expires_at.isoformat())
    print("License Key:", key)


if __name__ == "__main__":
    main()
