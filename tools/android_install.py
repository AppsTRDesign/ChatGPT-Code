from __future__ import annotations

import argparse
import sys

from tools.adb_utils import (
    device_from_serial,
    is_package_installed,
    run_adb_command,
    select_adb_device,
)


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Install an APK while skipping uninstall if the package is missing."
    )
    parser.add_argument("--apk", required=True, help="Path to the APK to install.")
    parser.add_argument(
        "--package",
        required=True,
        help="Application package name to uninstall before install.",
    )
    parser.add_argument(
        "--device",
        help="Use a specific adb serial instead of prompting for devices.",
    )
    args = parser.parse_args()

    device = device_from_serial(args.device) if args.device else select_adb_device()
    if is_package_installed(device, args.package):
        uninstall_result = run_adb_command(device, ["uninstall", args.package], check=False)
        if uninstall_result.returncode != 0:
            if uninstall_result.stderr:
                sys.stderr.write(uninstall_result.stderr)
            sys.exit(uninstall_result.returncode)
    else:
        print(
            f"Package {args.package} not installed on {device.serial}; skipping uninstall.",
            file=sys.stderr,
        )

    install_result = run_adb_command(device, ["install", "-r", args.apk], check=False)
    if install_result.stdout:
        sys.stdout.write(install_result.stdout)
    if install_result.stderr:
        sys.stderr.write(install_result.stderr)
    sys.exit(install_result.returncode)


if __name__ == "__main__":
    main()
