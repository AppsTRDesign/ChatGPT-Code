"""Helper CLI for generating machine-bound license keys."""
from __future__ import annotations

import sys
from typing import Optional

import click

from google_maps_gui.license_manager import LicenseManager


@click.command()
@click.option(
    "--months",
    "plan_months",
    type=click.Choice(["1", "3", "6"], case_sensitive=False),
    prompt="Plan süresi (1/3/6 ay)",
    help="Lisans süresi (ay cinsinden).",
)
@click.option(
    "--machine-id",
    "machine_id",
    help="Varsayılan olarak bu bilgisayarın makine kimliğini kullanır.",
)
def main(plan_months: str, machine_id: Optional[str]) -> None:
    """Generate and display a license key for the given machine."""
    manager = LicenseManager()
    if not machine_id:
        machine_id = manager.machine_id()
    machine_id = machine_id.strip().upper()
    key = manager.expected_key_for_machine(machine_id, int(plan_months))
    click.echo("==== License Generator ====")
    click.echo(f"Machine ID : {machine_id}")
    click.echo(f"Plan (months): {plan_months}")
    click.echo(f"License Key: {key}")


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:  # pragma: no cover - CLI convenience
        click.echo(f"Error: {exc}", err=True)
        sys.exit(1)
