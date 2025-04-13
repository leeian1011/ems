import mysql.connector
from mysql.connector import Error

def add_new_auditorium_section(section_id, section_name, price_modifier, seats_per_row):
    """
    Adds a completely new section to the auditorium by:
    1. First adding the section to the sections table
    2. Then adding the seats for that section
    
    Parameters:
    section_id (str): The ID of the new section (e.g., 'Z')
    section_name (str): The name of the new section (e.g., 'VIP Section')
    price_modifier (float): Multiplier for ticket prices (e.g., 2.0)
    seats_per_row (list): List of seat numbers to add
    """
    # Database connection parameters
    db_config = {
        "host": "localhost",
        "user": "root",
        "password": "",
        "database": "hevent"
    }
    
    try:
        # Establish connection to the database
        connection = mysql.connector.connect(**db_config)
        
        # Create a cursor to execute SQL queries
        cursor = connection.cursor()
        
        # Start a transaction
        connection.start_transaction()
        
        try:
            # Step 1: Check if the section already exists
            check_section_query = "SELECT COUNT(*) FROM sections WHERE section_id = %s"
            cursor.execute(check_section_query, (section_id,))
            section_exists = cursor.fetchone()[0]
            
            if section_exists:
                print(f"Section {section_id} already exists in the database.")
            else:
                # Step 2: Insert the new section into the sections table
                insert_section_query = """
                INSERT INTO sections (section_id, section_name, price_modifier) 
                VALUES (%s, %s, %s)
                """
                cursor.execute(insert_section_query, (section_id, section_name, price_modifier))
                print(f"Added new section {section_id} - {section_name} with price modifier {price_modifier}")
            
            # Step 3: Add the seats for this section
            seats_added = 0
            seats_skipped = 0
            
            for seat_number in seats_per_row:
                # Check if this seat already exists
                check_seat_query = """
                SELECT COUNT(*) FROM seats 
                WHERE section_id = %s AND row_number = %s AND seat_number = %s
                """
                cursor.execute(check_seat_query, (section_id, '1', seat_number))
                seat_exists = cursor.fetchone()[0]
                
                if seat_exists:
                    seats_skipped += 1
                    continue
                
                # Seat doesn't exist, insert it
                insert_seat_query = """
                INSERT INTO seats (section_id, row_number, seat_number, status) 
                VALUES (%s, %s, %s, %s)
                """
                seat_data = (section_id, '1', seat_number, 'available')
                
                cursor.execute(insert_seat_query, seat_data)
                seats_added += 1
            
            # Commit all changes if everything succeeded
            connection.commit()
            print(f"Added {seats_added} seats for section {section_id} (skipped {seats_skipped} existing seats)")
            print("New section setup completed successfully!")
            
        except Error as e:
            # If any error occurs, roll back all changes
            connection.rollback()
            print(f"An error occurred. Rolling back all changes. Error: {e}")
            
    except Error as e:
        print(f"Database Connection Error: {e}")
    
    finally:
        # Close the connection
        if connection.is_connected():
            cursor.close()
            connection.close()
            print("Database connection closed.")

# Example usage - Add a new VIP section 'Z' with seats 1-20
if __name__ == "__main__":
    # Define the new section details
    new_section_id = 'L'
    new_section_name = 'Side Seats'
    new_price_modifier = 1.0  # Twice the base ticket price
    new_seats = list(range(1, 6)) + list(range(36, 41))  # Seats 1 to 20
    
    # Add the new section
    add_new_auditorium_section(new_section_id, new_section_name, new_price_modifier, new_seats)

    # You can call this function again with different parameters to add more sections
    # For example:
    # add_new_auditorium_section('Y', 'Premium Balcony', 1.8, list(range(1, 31)))
