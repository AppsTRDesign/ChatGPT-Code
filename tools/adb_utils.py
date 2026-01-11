from __future__ import annotations

from dataclasses import dataclass
import subprocess
import sys
from typing import Iterable, List


@dataclass(frozen=True)
class AdbDevice:
    serial: str
    state: str
    description: str
    is_emulator: bool


def device_from_serial(serial: str) -> AdbDevice:
    is_emulator = serial.startswith("emulator-") or serial.startswith("localhost:")
    return AdbDevice(serial=serial, state="device", description="manual", is_emulator=is_emulator)


def list_adb_devices() -> List[AdbDevice]:
    result = subprocess.run(
        ["adb", "devices", "-l"],
        check=True,
        capture_output=True,
        text=True,
    )
    lines = result.stdout.splitlines()
    devices: List[AdbDevice] = []
    for line in lines[1:]:
        if not line.strip():
            continue
        parts = line.split()
        if len(parts) < 2:
            continue
        serial, state = parts[0], parts[1]
        if state != "device":
            continue
        description = " ".join(parts[2:]) if len(parts) > 2 else ""
        is_emulator = serial.startswith("emulator-") or serial.startswith("localhost:")
        devices.append(
            AdbDevice(
                serial=serial,
                state=state,
                description=description,
                is_emulator=is_emulator,
            )
        )
    return devices


def prompt_for_device(devices: Iterable[AdbDevice], label: str) -> AdbDevice:
    device_list = list(devices)
    if not device_list:
        raise RuntimeError("No devices available for selection.")
    print(f"Multiple {label} devices detected:", file=sys.stderr)
    for index, device in enumerate(device_list, start=1):
        description = f" ({device.description})" if device.description else ""
        print(f"{index}) {device.serial}{description}", file=sys.stderr)
    while True:
        choice = input("Select device number: ").strip()
        if not choice.isdigit():
            print("Please enter a valid number.", file=sys.stderr)
            continue
        selected = int(choice)
        if 1 <= selected <= len(device_list):
            return device_list[selected - 1]
        print("Selection out of range.", file=sys.stderr)


def select_adb_device() -> AdbDevice:
    devices = list_adb_devices()
    if not devices:
        raise RuntimeError("No adb devices detected.")
    physical_devices = [device for device in devices if not device.is_emulator]
    if physical_devices:
        if len(physical_devices) == 1:
            return physical_devices[0]
        return prompt_for_device(physical_devices, "physical")
    emulator_devices = [device for device in devices if device.is_emulator]
    if len(emulator_devices) == 1:
        return emulator_devices[0]
    return prompt_for_device(emulator_devices, "emulator")


def run_adb_command(
    device: AdbDevice,
    args: Iterable[str],
    *,
    check: bool = True,
) -> subprocess.CompletedProcess[str]:
    cmd = ["adb", "-s", device.serial, *args]
    return subprocess.run(cmd, check=check, text=True, capture_output=True)


def is_package_installed(device: AdbDevice, package: str) -> bool:
    result = run_adb_command(device, ["shell", "pm", "path", package], check=False)
    return bool(result.stdout.strip())
