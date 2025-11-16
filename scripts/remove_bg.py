#!/usr/bin/env python3
"""
Background Removal Script using rembg (U2-Net model)
High quality background removal for WooCommerce Shop CRM
"""

import sys
import os
from rembg import remove
from PIL import Image
import io

def remove_background(input_path, output_path):
    """
    Remove background from image using U2-Net model

    Args:
        input_path: Path to input image
        output_path: Path to save output PNG
    """
    try:
        # Read input image
        with open(input_path, 'rb') as input_file:
            input_data = input_file.read()

        # Remove background using U2-Net model
        # This preserves quality and handles complex backgrounds well
        output_data = remove(
            input_data,
            alpha_matting=True,           # Better edge refinement
            alpha_matting_foreground_threshold=240,
            alpha_matting_background_threshold=10,
            alpha_matting_erode_size=10
        )

        # Save as PNG with transparency
        with open(output_path, 'wb') as output_file:
            output_file.write(output_data)

        print(f"SUCCESS:{output_path}")
        return 0

    except Exception as e:
        print(f"ERROR:{str(e)}", file=sys.stderr)
        return 1

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: remove_bg.py <input_image> <output_image>")
        sys.exit(1)

    input_path = sys.argv[1]
    output_path = sys.argv[2]

    if not os.path.exists(input_path):
        print(f"ERROR:Input file not found: {input_path}", file=sys.stderr)
        sys.exit(1)

    sys.exit(remove_background(input_path, output_path))
