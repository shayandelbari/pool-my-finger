import time

TYPES = {
    "PISI": "Indoor swimming pool",
    "PIEX": "Outdoor swimming pool",
    "PATA": "Wading pool",
    "JEUD": "Play fountains"
}

class PoolType:
    def __init__(self, name : str, description : str):
        self.name = name
        self.description = description

class Pool:
    def __init__(self, 
                 name : str, 
                 type : PoolType,
                 url  : str,
                 address: str,
                 primary_image_url: str,
                 map_link: str,
                 geo_location: str,
                 phone: str,
                 createdAt: float = time.time(),
                 is_active: bool = True,
                 schedules: list = []):
        self.name = name
        self.type = type
        self.url = url
        self.address = address
        self.primary_image_url = primary_image_url
        self.map_link = map_link
        self.geo_location = geo_location
        self.phone = phone
        self.createdAt = createdAt
        self.is_active = is_active
        self.schedules = schedules

class Schedule:
    def __init__(self, day : str, start : float, end : float, createdAt : float = time.time()):
        self.day = day
        self.start = start
        self.end = end
        self.createdAt = createdAt