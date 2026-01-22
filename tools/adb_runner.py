from __future__ import annotations

import argparse
import sys

from tools.adb_utils import device_from_serial, run_adb_command, select_adb_device


def main() -> None:
    parser = argparse.ArgumentParser(
        description=(
            "Run adb commands with device selection that prefers physical devices and "
            "falls back to emulators when no physical device is attached."
        )
    )
    parser.add_argument(
        "--device",
        help="Use a specific adb serial instead of prompting for devices.",
    )
    parser.add_argument(
        "adb_args",
        nargs=argparse.REMAINDER,
        help="Arguments passed directly to adb (e.g. install path.apk).",
    )
    args = parser.parse_args()
    if not args.adb_args:
        parser.error("No adb arguments provided.")

    device = device_from_serial(args.device) if args.device else select_adb_device()
    result = run_adb_command(device, args.adb_args, check=False)
    if result.stdout:
        sys.stdout.write(result.stdout)
    if result.stderr:
        sys.stderr.write(result.stderr)
    sys.exit(result.returncode)


if __name__ == "__main__":
    main()
