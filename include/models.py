from __future__ import annotations

import os
from dataclasses import dataclass
from typing import Optional

from pydantic import BaseModel, HttpUrl

APP_TITLE = "NoaSoft Scraper Kontrol Paneli"
DEJAVU_FONT_PATH = os.path.join("assets", "DejaVuSans.ttf")  # Kullanıcı tarafından eklenecek


@dataclass
class Product:
    name: str
    price: str
    link: str
    image: str
    is_ad: bool


class ProductModel(BaseModel):
    name: str
    price: str
    link: HttpUrl | str
    image: HttpUrl | str
    is_ad: bool

    class Config:
        extra = "ignore"
        validate_assignment = True
