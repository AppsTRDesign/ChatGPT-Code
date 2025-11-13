from __future__ import annotations

import argparse
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from app.core.license import (
    PLAN_CHOICES,
    LicenseDuration,
    encode_license,
    machine_fingerprint,
)


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Generate a license key for the Telegram management suite.")
    parser.add_argument(
        "--machine",
        help="Machine fingerprint to license. Defaults to the current device's fingerprint.",
    )
    parser.add_argument(
        "--plan",
        choices=sorted(PLAN_CHOICES.keys()),
        help="Hazır lisans planı (1m, 3m, 6m)",
    )
    parser.add_argument("--years", type=int, default=0, help="Özel lisans süresi (yıl)")
    parser.add_argument("--months", type=int, default=0, help="Özel lisans süresi (ay)")
    parser.add_argument("--days", type=int, default=0, help="Özel lisans süresi (gün)")
    parser.add_argument("--label", help="Özel plan etiketi (varsayılan: 1Y 2M 3D formatı)")
    return parser


def main() -> None:
    parser = build_parser()
    args = parser.parse_args()
    machine_id = args.machine or machine_fingerprint()
    if args.plan:
        if args.years or args.months or args.days:
            parser.error("Özel süre ile hazır plan aynı anda kullanılamaz.")
        plan_label, duration = PLAN_CHOICES[args.plan]
        key = encode_license(machine_id, args.plan)
    else:
        if not (args.years or args.months or args.days):
            parser.error("Hazır plan seçin veya yıl/ay/gün değerlerinden en az birini girin.")
        duration = LicenseDuration(args.years, args.months, args.days)
        plan_label = args.label or duration.label()
        key = encode_license(machine_id, duration=duration, label=plan_label)
    expires_at = duration.apply(datetime.now(timezone.utc))
    print("Machine ID:", machine_id)
    print("Plan:", plan_label)
    print("Expires UTC:", expires_at.isoformat())
    print("License Key:", key)


if __name__ == "__main__":
    main()
