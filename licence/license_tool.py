"""Helper CLI for generating machine-bound license keys."""
from __future__ import annotations

import sys
from typing import Optional

import click

from google_maps_gui.license_manager import LicenseManager


@click.command()
@click.option(
    "--years",
    "plan_years",
    type=click.IntRange(0, 10),
    default=0,
    prompt="Yıl sayısı",
    show_default=True,
    help="Lisans süresi (yıl).",
)
@click.option(
    "--months",
    "plan_months",
    type=click.IntRange(0, 120),
    default=0,
    prompt="Ay sayısı",
    show_default=True,
    help="Lisans süresi (ay).",
)
@click.option(
    "--days",
    "plan_days",
    type=click.IntRange(0, 366),
    default=0,
    prompt="Gün sayısı",
    show_default=True,
    help="Lisans süresi (gün).",
)
@click.option(
    "--machine-id",
    "machine_id",
    help="Lisans oluşturulacak hedef bilgisayarın makine kimliği.",
)
def main(
    plan_years: int, plan_months: int, plan_days: int, machine_id: Optional[str]
) -> None:
    """Generate and display a license key for the given machine."""
    manager = LicenseManager()
    if not machine_id:
        machine_id = click.prompt(
            "Makine kimliği",
            default=manager.machine_id(),
            show_default=True,
        )
    machine_id = machine_id.strip().upper()
    if plan_years == plan_months == plan_days == 0:
        raise click.BadParameter("En az bir zaman dilimi seçmelisiniz.")
    key = manager.expected_key_for_machine(
        machine_id, int(plan_years), int(plan_months), int(plan_days)
    )
    click.echo("==== License Generator ====")
    click.echo(f"Machine ID : {machine_id}")
    click.echo(f"Years : {plan_years}")
    click.echo(f"Months: {plan_months}")
    click.echo(f"Days  : {plan_days}")
    click.echo(f"License Key: {key}")


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:  # pragma: no cover - CLI convenience
        click.echo(f"Error: {exc}", err=True)
        sys.exit(1)
