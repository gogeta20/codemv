"""Inserta las 9 ligas configuradas en la DB. Ejecutar una sola vez."""
import sys
import os
sys.path.insert(0, os.path.dirname(__file__))

from db import seed_ligas

if __name__ == "__main__":
    seed_ligas()
