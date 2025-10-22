import os
from fontTools.ttLib import TTFont

def convert_fonts():
    # Получаем путь к директории скрипта
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # Собираем все TTF-файлы в директории
    ttf_files = [f for f in os.listdir(script_dir) 
                if f.lower().endswith('.ttf')]
    
    if not ttf_files:
        print("Не найдено TTF-файлов в текущей директории.")
        return

    print(f"Найдено TTF-файлов: {len(ttf_files)}")
    
    for ttf_file in ttf_files:
        base_name = os.path.splitext(ttf_file)[0]
        input_path = os.path.join(script_dir, ttf_file)
        
        print(f"\nКонвертация: {ttf_file}")
        
        try:
            # Конвертация в WOFF
            woff_path = os.path.join(script_dir, f"{base_name}.woff")
            with TTFont(input_path) as font:
                font.flavor = 'woff'
                font.save(woff_path)
            print(f"Создан WOFF: {os.path.basename(woff_path)}")

            # Конвертация в WOFF2
            woff2_path = os.path.join(script_dir, f"{base_name}.woff2")
            with TTFont(input_path) as font:
                font.flavor = 'woff2'
                font.save(woff2_path)
            print(f"Создан WOFF2: {os.path.basename(woff2_path)}")
                
        except Exception as e:
            print(f"Ошибка при конвертации {ttf_file}: {str(e)}")

if __name__ == "__main__":
    convert_fonts()
    print("\nОбработка завершена!")