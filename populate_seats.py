import mysql.connector
from mysql.connector import Error

def populate_auditorium_seats():
    """
    Connects to the auditorium_booking database and populates the seats table
    with the specified seating layout for each section.
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
        
        # Define the updated seating layout with the new seat ranges
        seating_layout = {
            # Main sections with updated seat ranges
            'A': {'seats_per_row': list(range(1, 8+1)) + list(range(15, 33+1)) + list(range(36, 43+1))},  # Seats 1 to 43 for section A
            'B': {'seats_per_row': list(range(1, 10+1)) + list(range(15, 34+1)) + list(range(36, 44+1))},  # Seats 1 to 43 for section A
            'C': {'seats_per_row': list(range(1, 11+1)) + list(range(15, 33+1)) + list(range(36, 46+1))},  # Seats 1 to 43 for section A
            'D': {'seats_per_row': list(range(1, 12+1)) + list(range(15, 34+1)) + list(range(36, 47+1))},  # Seats 1 to 43 for section A
            'E': {'seats_per_row': list(range(1, 12+1)) + list(range(15, 31+1)) + list(range(36, 47+1))},  # Seats 1 to 43 for section A
            'F': {'seats_per_row': list(range(1, 12+1)) + list(range(15, 32+1)) + list(range(36, 47+1))},  # Seats 1 to 43 for section A
            
            # Keeping the remaining sections as they were in the original script
            'G': {'seats_per_row': list(range(1, 12+1)) + list(range(15, 31+1)) + list(range(36, 47+1))},  # Seats 1 to 43 for section A
            'H': {'seats_per_row': list(range(1, 11+1)) + list(range(15, 32+1)) + list(range(36, 46+1))},  # Seats 1 to 43 for section A
            'J': {'seats_per_row': list(range(1, 10+1)) + list(range(15, 29+1)) + list(range(36, 45+1))},  # Seats 1 to 43 for section A
            'K': {'seats_per_row': list(range(1, 8+1)) + list(range(15, 30+1)) + list(range(36, 43+1))},  # Seats 1 to 43 for section A
            'L': {'seats_per_row': list(range(1, 5+1)) + list(range(36, 40+1))},  # Seats 1 to 43 for section A
            
            # Back sections
            'AA': {'seats_per_row': list(range(50, 0, -1))},  # From 50 down to 15
            'BB': {'seats_per_row': list(range(50, 0, -1))},
            'CC': {'seats_per_row': list(range(50, 0, -1))},
            'DD': {'seats_per_row': list(range(49, 36, -1)) + list(range(35, 0, -1))},
            'EE': {'seats_per_row': list(range(48, 36, -1)) + list(range(12, 0, -1))},
        }
        
        # Optional: Clear existing seats before inserting new ones
        cursor.execute("SELECT COUNT(*) FROM booking_details")
        booking_count = cursor.fetchone()[0]
        
        if booking_count > 0:
            print("WARNING: There are existing bookings in the system.")
            print("Skipping truncate operation to preserve data integrity.")
            print("Will insert new seats only if they don't already exist.")
            
            # For each section and seat, check if it exists - if not, insert it
            for section_id, section_info in seating_layout.items():
                seat_numbers = section_info['seats_per_row']
                
                for seat_number in seat_numbers:
                    # Check if this seat already exists
                    check_query = """
                    SELECT COUNT(*) FROM seats 
                    WHERE section_id = %s AND row_number = %s AND seat_number = %s
                    """
                    cursor.execute(check_query, (section_id, '1', seat_number))
                    exists = cursor.fetchone()[0]
                    
                    if exists == 0:
                        # Seat doesn't exist, insert it
                        insert_query = """
                        INSERT INTO seats (section_id, row_number, seat_number, status) 
                        VALUES (%s, %s, %s, %s)
                        """
                        seat_data = (section_id, '1', seat_number, 'available')
                        
                        try:
                            cursor.execute(insert_query, seat_data)
                            print(f"Added new seat {seat_number} in section {section_id}")
                        except Error as e:
                            print(f"Error inserting seat {seat_number} in section {section_id}: {e}")
                
                # Commit after each section
                connection.commit()
            
        else:
            # METHOD 2: If no bookings exist, we can safely use DELETE instead of TRUNCATE
            print("No existing bookings found. Safe to delete all seats.")
            delete_query = "DELETE FROM seats"
            cursor.execute(delete_query)
            print("Deleted all existing seats.")
            
            # Insert all seats into the database
            for section_id, section_info in seating_layout.items():
                seat_numbers = section_info['seats_per_row']
                seats_added = 0
                
                for seat_number in seat_numbers:
                    # Prepare the INSERT statement
                    insert_query = """
                    INSERT INTO seats (section_id, row_number, seat_number, status) 
                    VALUES (%s, %s, %s, %s)
                    """
                    # Define the data for the query
                    seat_data = (section_id, '1', seat_number, 'available')
                    
                    # Execute the query
                    try:
                        cursor.execute(insert_query, seat_data)
                        seats_added += 1
                    except Error as e:
                        print(f"Error inserting seat {seat_number} in section {section_id}: {e}")
                
                # Commit after each section
                connection.commit()
                print(f"Added {seats_added} seats for section {section_id}")
        
        print("Seat population completed successfully!")
        
    except Error as e:
        print(f"Database Error: {e}")
    
    finally:
        # Close the connection
        if connection.is_connected():
            cursor.close()
            connection.close()
            print("Database connection closed.")

if __name__ == "__main__":
    populate_auditorium_seats()
